<?php
namespace Bit\Metric;

use Bit\Metric\UserStatTable;
use Bitrix\Blog\CommentTable;
use Bitrix\Blog\PostTable;
use Bitrix\Calendar\Internals\EventTable;
use Bitrix\Forum\MessageTable;
use Bitrix\Im\Model\MessageTable as ImMessageTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Socialnetwork\WorkgroupTable;
use Bitrix\Tasks\TaskTable;
use Bitrix\Socialnetwork\UserContentViewTable;
use Bitrix\Intranet\UStat\UserDayTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Vote\VoteTable;

class UserStat
{
    private array $filter = [];
    private DateTime $dateFrom;
    private DateTime $dateTo;

    public function __construct($dateFrom = null, $dateTo = null)
    {
        $this->dateTo = $dateTo;
        $this->dateFrom = $dateFrom;

        if (!is_null($dateFrom) && !is_null($dateTo)) {
            $this->filter = [
                '>=DAY' => $dateFrom->format('Y-m-d'),
                '<=DAY' => $dateTo->format('Y-m-d')
            ];
        }
    }

    public function update()
    {
        if (Loader::includeModule('blog')) {
            $this->insertData(self::getComments());
            $this->insertData(self::getPosts());
        } else if (Loader::includeModule('forum')) {
            $this->insertData(self::getComments());
        }

        if (Loader::includeModule('intranet')) {
            $this->insertData(self::getLikes());
        }
        if (Loader::includeModule('socialnetwork')) {
            $this->insertData(self::getGroups());
            $this->insertData(self::getSurveys());
        }
        if (Loader::includeModule('calendar')) {
            $this->insertData(self::getEvents());
        }
        if (Loader::includeModule('im')) {
            $this->insertData(self::getMessages());
        }
        if (Loader::includeModule('tasks')) {
            $this->insertData(self::getTasks());
        }
    }

    public function insertData($data) {

        print_r('count: ' . count($data) . PHP_EOL);
        foreach ($data as $stat) {
			if (!$stat['USER_ID'] || !$stat['DAY']) {
                continue;
            }
            $userDayStat = UserStatTable::getByPrimary([
                'USER_ID' => $stat['USER_ID'],
                'DAY' => $stat['DAY'],
            ])->fetchObject();

            if (!$userDayStat) {
                UserStatTable::add($stat);
                continue;
            }

            foreach ($stat as $key => $value) {
                if (in_array($key, ['USER_ID', 'DAY'])) {
                    continue;
                }
                $userDayStat->set($key, $value);
            }
            $userDayStat->save();
        }
    }

    public static function importHistorical()
    {
        $userStat = new UserStat();
        $userStat->update();
    }

    public function mergeData(&$data, $newRows) {
        foreach ($newRows as $key => $value) {
            if (isset($data[$key])) {
                $data[$key] = array_merge($data[$key], $value);
                continue;
            }
            $data[$key] = $value;
        }
    }

    public function getLikes(): array
    {
        if (!Loader::includeModule('intranet')) {
            return [];
        }
        $likes = [];
        $filter = $this->filter;
        foreach ($filter as &$date) {
            $date = (new DateTime($date, 'Y-m-d'))->format('d.m.Y');
        }
        $likesQuery = UserDayTable::getList([
            'filter' => $filter,
            'select' => [
                'USER_ID',
                'DAY',
                'LIKES'
            ],
        ]);
        while ($like = $likesQuery->fetch()) {
            if ($like['LIKES'] == 0) continue;

            $likes[$like['USER_ID'] . '_' . $like['DAY']->format('Ymd')] = $like;
        }

        return $likes;
    }

    public function getComments()
    {
        $comments = [];

        if (Loader::includeModule('forum')) {
            $forumMessages = MessageTable::getList([
                'filter' => [
                    'SERVICE_DATA' => null,
                    ...$this->filter
                ],
                'select' => [
                    'USER_ID' => 'AUTHOR_ID',
                    'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'POST_DATE'),
                    'COMMENTS' => new ExpressionField('COMMENTS', 'count(*)'),
                ]
            ]);
            while ($message = $forumMessages->fetch()) {
                $comments[$message['USER_ID'] . '_' . $message['DAY']->format('Ymd')] = $message;
            }
        }

        if (Loader::includeModule('blog')) {
            $blogComments = CommentTable::getList([
                'filter' => $this->filter,
                'select' => [
                    'USER_ID' => 'AUTHOR_ID',
                    'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_CREATE'),
                    'COMMENTS' => new ExpressionField('COMMENTS', 'count(*)'),
                ],
            ]);
            while ($message = $blogComments->fetch()) {
                if (isset($comments[$message['USER_ID'] . '_' . $message['DAY']->format('Ymd')])) {
                    $comments[$message['USER_ID'] . '_' . $message['DAY']->format('Ymd')]['COMMENTS'] += $message['COMMENTS'];
                } else {
                    $comments[$message['USER_ID'] . '_' . $message['DAY']->format('Ymd')] = $message;
                }
            }
        }

        return $comments;
    }

    public function getPosts()
    {
        if (!Loader::includeModule('blog')) {
            return [];
        }

        $posts = [];

        $postsQuery = PostTable::getList([
            'filter' => $this->filter,
            'select' => [
                'USER_ID' => 'AUTHOR_ID',
                'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_CREATE'),
                'POSTS' => new ExpressionField('COMMENTS', 'count(*)'),
            ],
            'runtime' => [
                new ExpressionField('POST_ID', "concat('BLOG_POST-', %s)", 'ID'),
            ]
        ]);
        while ($post = $postsQuery->fetch()) {
            $posts[$post['USER_ID'] . '_' . $post['DAY']->format('Ymd')] = $post;
        }

        if (Loader::includeModule('socialnetwork')) {
            $postViewsQuery = UserContentViewTable::getList([
                'filter' => $this->filter,
                'select' => [
                    'USER_ID',
                    'DAY', 'POSTS_VIEWS'
                ],
                'runtime' => [
                    'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_VIEW'),
                    'POSTS_VIEWS' => new ExpressionField('POSTS_VIEWS', 'count(*)'),
                    new ExpressionField('POST_ID', "replace(%s, 'BLOG_POST-', '')", 'CONTENT_ID'),
                    new ReferenceField(
                        'POSTS',
                        PostTable::getEntity(),
                        Join::on('this.POST_ID', 'ref.ID'),
                        ['join_type' => 'RIGHT']
                    ),

                ]
            ]);
            while ($postsView = $postViewsQuery->fetch()) {
				if (!$postsView['DAY']) {
                    continue;
                }
                $key = $postsView['USER_ID'] . '_' . $postsView['DAY']->format('Ymd');
                $posts[$key] = array_merge($posts[$key] ?? [], $postsView);
            }
        }

        return $posts;
    }

    public function getTasks()
    {
        if (!Loader::includeModule('tasks')) {
            return [];
        }

        $tasks = [];

        $tasksQuery = TaskTable::GetList([
            'select' => [
                'USER_ID' => 'RESPONSIBLE_ID',
                'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'CREATED_DATE'),
                'TASKS' => new ExpressionField('TASKS', 'COUNT(*)')
            ],
            'filter' => [
                ...$this->filter,
                'ZOMBIE' => 'N'
            ],
        ]);

        while ($task = $tasksQuery->fetch()) {
            $tasks[$task['USER_ID'] . '_' . $task['DAY']->format('Ymd')] = $task;
        }

        $finishedTasksQuery = TaskTable::GetList([
            'select' => [
                'USER_ID' => 'RESPONSIBLE_ID',
                'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'CLOSED_DATE'),
                'TASKS_FINISHED' => new ExpressionField('TASKS_FINISHED', 'COUNT(*)')
            ],
            'filter' => [
                ...$this->filter,
                'ZOMBIE' => 'N',
                'STATUS' => '5'
            ],
        ]);

        while ($finishedTask = $finishedTasksQuery->fetch()) {
            $key = $finishedTask['USER_ID'] . '_' . $finishedTask['DAY']->format('Ymd');
            $tasks[$key] = array_merge($tasks[$key] ?? [], $finishedTask);
        }

        return $tasks;

    }

    public function getGroups()
    {
        if (!Loader::includeModule('socialnetwork')) {
            return [];
        }
        $groups = [];
        $groupsQuery = WorkGroupTable::getList([
            'filter' => $this->filter,
            'select' => [
                'USER_ID' => 'OWNER_ID',
                'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_CREATE'),
                'GROUPS' => new ExpressionField('GROUPS', 'COUNT(*)')
            ]
        ]);

        while ($group = $groupsQuery->fetch()) {
            $groups[$group['USER_ID'] . '_' . $group['DAY']->format('Ymd')] = $group;
        }

        return $groups;
    }

    public function getEvents()
    {
        if (!Loader::includeModule('calendar')) {
            return [];
        }
        $events = [];
        if (!is_null($this->dateTo) && !is_null($this->dateFrom)) {
            $filter = [
                '<=DAY_FROM' => $this->dateTo->format('Y-m-d'),
                '>=DAY_TO' => $this->dateFrom->format('Y-m-d'),
            ];
        }
        $eventsQuery = EventTable::getlist([
            'select' => [
                'USER_ID' => 'OWNER_ID',
                'DAY_FROM' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_FROM'),
                'DAY_TO' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_TO'),
                'EVENTS' => new ExpressionField('EVENTS', 'COUNT(*)')
            ],
            'filter' => $filter ?? []
        ]);

        while ($event = $eventsQuery->fetch()) {
            $dateFrom = $event['DAY_FROM'];
            $dateTo = $event['DAY_TO'];

            unset($event['DAY_TO']);
            unset($event['DAY_FROM']);

            do {
                $dateFrom->add('P1D');

                $event['DAY'] = clone $dateFrom;

                $key = $event['USER_ID'] . '_' . $event['DAY']->format('Ymd');
                if (isset($events[$key])) {
                    $events[$key]['EVENTS'] += $event['EVENTS'];
                } else {
                    $events[$key] = $event;
                }
            } while ($dateFrom->format('Ymd') < $dateTo->format('Ymd'));
        }

        return $events;
    }

    public function getSurveys()
    {
        if (!Loader::includeModule('vote')) {
            return [];
        }
        $surveys = [];
        $surveysQuery = VoteTable::getlist([
            'select' => [
                'USER_ID' => 'AUTHOR_ID',
                'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_START'),
                'SURVEYS' => new ExpressionField('SURVEYS', 'COUNT(*)')
            ],
            'filter' => $this->filter,
        ]);

        while ($survey = $surveysQuery->fetch()) {
            $surveys[$survey['USER_ID'] . '_' . $survey['DAY']->format('Ymd')] = $survey;
        }

        return $surveys;
    }

    public function getMessages()
    {
        if (!Loader::includeModule('im')) {
            return [];
        }
        $messages = [];
        $messagesQuery = ImMessageTable::getList([
            'filter' => [
                ...$this->filter,
                'NOTIFY_MODULE' => 'im',
                '!AUTHOR_ID' => '0',
                'NOTIFY_TYPE' => '0',
                'NOTIFY_EVENT' => ['private', 'group']
            ],
            'select' => [
                'USER_ID' => 'AUTHOR_ID',
                'DAY' => new ExpressionField('DAY', 'cast(%s as date)', 'DATE_CREATE'),
                'MESSAGES' => new ExpressionField('MESSAGES', 'COUNT(*)')
            ]
        ]);

        while ($message = $messagesQuery->fetch()) {
            $messages[$message['USER_ID'] . '_' . $message['DAY']->format('Ymd')] = $message;
        }

        return $messages;
    }
}