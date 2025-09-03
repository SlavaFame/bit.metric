<?php
namespace Bit\Metric\Controller;

use Bit\Metric\UserStat;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Security\Sign\BadSignatureException;

set_time_limit(120);

class Import extends Controller
{
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_PROGRESS = 'PROGRESS';

    private int $processedItems = 0;
    private int $totalItems = 0;
    private string $day = '';
    private ?string $action = null;
    private string|array|null $processToken = '';
    private bool $isNewProcess = true;

    private array $actions = [
    ];
    private array $fieldToStoreInProcess = [
        'totalItems',
        'processedItems',
        'day',
        'action'
    ];
    private bool $isCompleted = false;

    /**
     * Initializes controller.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->processToken = $this->request->get('PROCESS_TOKEN');

        $progressData = $this->getProgressParameters();
        if (!empty($progressData)) {
            $this->isNewProcess = (empty($progressData['processToken']) || $progressData['processToken'] !== $this->processToken);
            if (!$this->isNewProcess) {
                foreach ($this->fieldToStoreInProcess as $fieldName) {
                    if (isset($progressData[$fieldName])) {
                        $this->{$fieldName} = $progressData[$fieldName];
                    }
                }
            } else {
                $this->day = $_POST['startDate'];
            }
        } else {
            $this->day = $_POST['startDate'];
        }
    }

    /**
     * @throws \Exception
     */
    public function importAction(): ?array
    {
        if ($this->isNewProcess) {
            $this->processedItems = 0;
            $this->action = 0;
            $this->totalItems = (new DateTime($this->day, 'd.m.Y'))->getDiff(new DateTime())->format('%a');
            $this->saveProgressParameters();
        }

        $date = new DateTime($this->day, 'd.m.Y');
        $date->add($this->processedItems . ' day');

        if ($this->processedItems >= $this->totalItems) {
            $date = new DateTime($this->day, 'd.m.Y');

            $this->action += 1;
            $this->processedItems = 0;
        }
        if ($this->action >= count($this->actions)) {
            $this->isCompleted = true;
            return $this->preformAnswer('Импорт успешно выполнен! Страницу можно закрыть');
        }
        $importer = new UserStat($date, $date);

        $method = $this->actions[$this->action];
        if (!$method) {
            $this->addError(new Error('Не удалось получить список для импорта'));
            return null;
        }
        $method = 'get' . ucfirst($method);

        if (method_exists($importer, $method)) {
            $importer->insertData(
            $importer->$method()
            );
        }

        $this->processedItems += 1;
        $this->saveProgressParameters();

        $message = 'Импорт истории ' . match ($this->actions[$this->action]) {
                'likes' => 'лайков',
                'posts' => 'постов',
                'tasks' => 'задач',
                'events' => 'событий',
                'messages' => 'сообщений',
                'comments' => 'комментариев',
                'surveys' => 'опросов',
                'groups' => 'групп',
            } . ' за ' . $date->format('m.d.Y');

        return $this->preformAnswer($message, $this->totalItems);
    }


    /**
     * Save progress parameters.
     *
     * @return void
     */
    protected function saveProgressParameters(): void
    {
        // store state
        $progressData = [
            'processToken' => $this->processToken,
        ];
        foreach ($this->fieldToStoreInProcess as $fieldName) {
            $progressData[$fieldName] = $this->{$fieldName};
        }

        $progressData = (new Signer())->sign(Json::encode($progressData));

        ($this->getLocalSession())->set($this->getProgressParameterOptionName(), $progressData);
    }

    /**
     * Load progress parameters.
     *
     * @return array
     */
    protected function getProgressParameters()
    {
        $progressData = ($this->getLocalSession())->get($this->getProgressParameterOptionName());

        if ($progressData) {
            try {
                $progressData = Json::decode((new Signer())->unsign($progressData));
            } catch (BadSignatureException $exception) {
                $progressData = [];
            } catch (ArgumentException $exception) {
                $progressData = [];
            }
        }

        if (!is_array($progressData)) {
            $progressData = [];
        }

        return $progressData;
    }

    /**
     * Removes progress parameters.
     *
     * @return void
     */
    protected function clearProgressParameters(): void
    {
        ($this->getLocalSession())->offsetUnset($this->getProgressParameterOptionName());
    }

    /**
     * Returns progress option name
     *
     * @return string
     */
    protected function getProgressParameterOptionName(): string
    {
        return 'chelbit_import_stat';
    }

    private function getLocalSession()
    {
        return Application::getInstance()->getLocalSession($this->getProgressParameterOptionName() . '_session');
    }

    protected function preformAnswer(string $msg, ...$logs): array
    {
        $status = ($this->isCompleted ? self::STATUS_COMPLETED : self::STATUS_PROGRESS);
        if ($status == self::STATUS_COMPLETED) {
            $msg = 'Импорт успешно завершён! Можете закрыть страницу';
        }
        $res = [
            'STATUS' => $status,
            'PROCESSED_ITEMS' => $this->processedItems,
            'TOTAL_ITEMS' => $this->totalItems,
            'SUMMARY_HTML' => $msg
        ];
        return [...$res, ...$logs];
    }
}
