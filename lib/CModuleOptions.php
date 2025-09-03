<?php
namespace Bit\Metric;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use CAdminTabControl; 
use CMain;
use FileInput;

\CJSCore::init("color_picker");

class CModuleOptions
{
    public $arCurOptionValues = array();

    private $module_id = '';
    private $arTabs = array();
    private $arGroups = array();
    private $arOptions = array();
    private $need_access_tab = false;

    public function __construct($module_id, $arTabs, $arGroups, $arOptions, $need_access_tab = false)
    {
        $this->module_id = $module_id;
        $this->arTabs = $arTabs;
        $this->arGroups = $arGroups;
        $this->arOptions = $arOptions;
        $this->need_access_tab = $need_access_tab;

        if ($need_access_tab)
            $this->arTabs[] = array(
                'DIV' => 'edit_access_tab',
                'TAB' => 'Права доступа',
                'ICON' => '',
                'TITLE' => 'Настройка прав доступа'
            );

        if ($_REQUEST['update'] == 'Y' && check_bitrix_sessid()) {
            $this->SaveOptions();
            if ($this->need_access_tab) {
                $this->SaveGroupRight();
            }
        }

        $this->GetCurOptionValues();
    }

    private function SaveOptions()
    {
        foreach ($this->arOptions as $opt => $arOptParams) {
            if ($arOptParams['TYPE'] != 'CUSTOM') {
                $val = $_REQUEST[$opt];

                if ($arOptParams['TYPE'] == 'CHECKBOX' && $val != 'Y')
                    $val = 'N';
                elseif (is_array($val))
                    $val = serialize($val);
                if ($val == '')
                    Option::delete($this->module_id, array(
                            "name" => $opt,
                        )
                    );
                else
                    Option::set($this->module_id, $opt, $val);
            } elseif ($opt == 'price_brands' || $opt == 'replace_categories') {
                $val = $_REQUEST[$opt];
                $val = serialize($val);
                if ($val == '')
                    Option::delete($this->module_id, array(
                            "name" => $opt,
                        )
                    );
                else
                    Option::set($this->module_id, $opt, $val);
            }
        }
    }

    private function SaveGroupRight()
    {
        CMain::DelGroupRight($this->module_id);
        $GROUP = $_REQUEST['GROUPS'];
        $RIGHT = $_REQUEST['RIGHTS'];

        foreach ($GROUP as $k => $v) {
            if ($k == 0) {
                Option::set($this->module_id, 'GROUP_DEFAULT_RIGHT', $RIGHT[0], 'Right for groups by default');
            } else {
                CMain::SetGroupRight($this->module_id, $GROUP[$k], $RIGHT[$k]);
            }
        }
    }

    private function GetCurOptionValues()
    {
        foreach ($this->arOptions as $opt => $arOptParams) {
            if ($arOptParams['TYPE'] != 'CUSTOM') {
                $this->arCurOptionValues[$opt] = Option::get($this->module_id, $opt, $arOptParams['DEFAULT']);
                if (in_array($arOptParams['TYPE'], array('MSELECT')))
                    $this->arCurOptionValues[$opt] = unserialize($this->arCurOptionValues[$opt]);
            }
        }
    }

    public function ShowHTML()
    {
        global $APPLICATION;

        $arP = array();

        foreach ($this->arGroups as $group_id => $group_params)
            $arP[$group_params['TAB']][$group_id] = array();

        if (is_array($this->arOptions)) {
            foreach ($this->arOptions as $option => $arOptParams) {
                $val = $this->arCurOptionValues[$option];

                if ($arOptParams['SORT'] < 0 || !isset($arOptParams['SORT']))
                    $arOptParams['SORT'] = 0;

                $label = (isset($arOptParams['TITLE']) && $arOptParams['TITLE'] != '') ? $arOptParams['TITLE'] : '';
                $opt = htmlspecialchars($option);
                switch ($arOptParams['TYPE']) {
                    case 'DATE':
                        $input = '<input type="text" name="' . $opt . '" id="' . $opt . '" value="' . $val . '" /><script>$(\'#' . $opt . '\').datetimepicker({lang:"en",format:"F d, Y H:i", step:"30"});</script>';
                        break;
                    case 'CHECKBOX':
                        $input = '<input type="checkbox" name="' . $opt . '" id="' . $opt . '" value="Y"' . ($val == 'Y' ? ' checked' : '') . ' ' . ($arOptParams['REFRESH'] == 'Y' ? 'onclick="document.forms[\'' . $this->module_id . '\'].submit();"' : '') . ' />';
                        break;
                    case 'TEXT':
                        if (!isset($arOptParams['COLS']))
                            $arOptParams['COLS'] = 25;
                        if (!isset($arOptParams['ROWS']))
                            $arOptParams['ROWS'] = 5;
                        $input = '<textarea cols="' . $arOptParams['COLS'] . '" rows="' . $arOptParams['ROWS'] . '" name="' . $opt . '">' . htmlspecialchars($val) . '</textarea>';
                        if ($arOptParams['REFRESH'] == 'Y')
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        break;
                    case 'SELECT':
                        $input = SelectBoxFromArray($opt, $arOptParams['VALUES'], $val, '', '', ($arOptParams['REFRESH'] == 'Y' ? true : false), ($arOptParams['REFRESH'] == 'Y' ? str_replace('.', '_', $this->module_id) : ''));
                        break;
                    case 'MSELECT':
                        $input = SelectBoxMFromArray($opt . '[]', $arOptParams['VALUES'], $val);
                        if ($arOptParams['REFRESH'] == 'Y')
                            $input .= '<input type="submit" name="refresh" value="OK" />';

                        if ($arOptParams['GET_SITE'] == 'Y')
                            $input .= '<a style="margin-left:15px;" href="javascript:void(0)" class="get_site" data-get="' . $opt . '">' . Loc::getMessage('PARSER_OPTIONS_GET_SITE_' . $opt) . '</a>';
                        break;
                    case 'COLORPICKER':
                        if (!isset($arOptParams['FIELD_SIZE']))
                            $arOptParams['FIELD_SIZE'] = 25;
                        ob_start();
                        echo '<input id="__CP_PARAM_' . $opt . '" name="' . $opt . '" size="' . $arOptParams['FIELD_SIZE'] . '" value="' . htmlspecialchars($val) . '" type="text" ' . ($arOptParams['FIELD_READONLY'] == 'Y' ? 'readonly' : '') . ' />                                
                            <script>
                            BX.ready(function(){
                                let element = document.getElementById("__CP_PARAM_' . $opt . '"); 

                                let picker = new BX.ColorPicker({
                                    bindElement: element,
                                        defaultColor: "#dd046a",
                                        allowCustomColor: true,
                                        onColorSelected: function (item) {
                                            element.value = item
                                        },
                                        popupOptions: {
                                            angle: true, 
                                            autoHide: true,
                                            closeByEsc: true
                                        }
                                });
                                        
                                element.addEventListener("click", function() {
                                    picker.open();
                                }); 
                            });
                            </script>';
                        $input = ob_get_clean();
                        if ($arOptParams['REFRESH'] == 'Y')
                            $input .= '<input type="submit" name="refresh" value="OK">';
                        break;
                    case 'FILE':
                        if (!isset($arOptParams['FIELD_SIZE']))
                            $arOptParams['FIELD_SIZE'] = 25;
                        if (!isset($arOptParams['BUTTON_TEXT']))
                            $arOptParams['BUTTON_TEXT'] = '...';

                        FileInput::createInstance([
                            "name" => $opt['name'],
                            "description" => true,
                            "upload" => true,
                            "allowUpload" => "I",
                            "medialib" => true,
                            "fileDialog" => true,
                            "cloud" => true,
                            "delete" => true,
                            "maxCount" => 1,
                        ])->show($opt['value']);

                        $input = '<input id="__FD_PARAM_' . $opt . '" name="' . $opt . '" size="' . $arOptParams['FIELD_SIZE'] . '" value="' . htmlspecialchars($val) . '" type="text" style="float: left;" ' . ($arOptParams['FIELD_READONLY'] == 'Y' ? 'readonly' : '') . ' />
                                    <input value="' . $arOptParams['BUTTON_TEXT'] . '" type="button" onclick="window.BX_FD_' . $opt . '();" />
                                    <script>
                                        setTimeout(function(){
                                            if (BX("bx_fd_input_' . strtolower($opt) . '"))
                                                BX("bx_fd_input_' . strtolower($opt) . '").onclick = window.BX_FD_' . $opt . ';
                                        }, 200);
                                        window.BX_FD_ONRESULT_' . $opt . ' = function(filename, filepath)
                                        {
                                            var oInput = BX("__FD_PARAM_' . $opt . '");
                                            if (typeof filename == "object")
                                                oInput.value = filename.src;
                                            else
                                                oInput.value = (filepath + "/" + filename).replace(/\/\//ig, \'/\');
                                        }
                                    </script>';
                        if ($arOptParams['REFRESH'] == 'Y')
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        break;
                    case 'CUSTOM':
                        $input = $arOptParams['VALUE'];
                        break;
                    default:
                        if (!isset($arOptParams['SIZE']))
                            $arOptParams['SIZE'] = 25;
                        if (!isset($arOptParams['MAXLENGTH']))
                            $arOptParams['MAXLENGTH'] = 255;
                        $input = '<input type="' . ($arOptParams['TYPE'] == 'INT' ? 'number' : 'text') . '" size="' . $arOptParams['SIZE'] . '" maxlength="' . $arOptParams['MAXLENGTH'] . '" value="' . htmlspecialchars($val) . '" name="' . htmlspecialchars($option) . '" />';
                        if ($arOptParams['REFRESH'] == 'Y')
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        break;
                }

                if (isset($arOptParams['NOTES']) && $arOptParams['NOTES'] != '')
                    $input .= '<div class="notes">
                                    <table cellspacing="0" cellpadding="0" border="0" class="notes">
                                        <tbody>
                                            <tr class="top">
                                                <td class="left"><div class="empty"></div></td>
                                                <td><div class="empty"></div></td>
                                                <td class="right"><div class="empty"></div></td>
                                            </tr>
                                            <tr>
                                                <td class="left"><div class="empty"></div></td>
                                                <td class="content">
                                                    ' . $arOptParams['NOTES'] . '
                                                </td>
                                                <td class="right"><div class="empty"></div></td>
                                            </tr>
                                            <tr class="bottom">
                                                <td class="left"><div class="empty"></div></td>
                                                <td><div class="empty"></div></td>
                                                <td class="right"><div class="empty"></div></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>';

                $arP[$this->arGroups[$arOptParams['GROUP']]['TAB']][$arOptParams['GROUP']]['OPTIONS'][] = $label != '' ? '<tr><td valign="top" width="40%">' . $label . '</td><td valign="top" nowrap>' . $input . '</td></tr>' : '<tr><td valign="top" colspan="2" align="center">' . $input . '</td></tr>';
                $arP[$this->arGroups[$arOptParams['GROUP']]['TAB']][$arOptParams['GROUP']]['OPTIONS_SORT'][] = $arOptParams['SORT'];
            }

            $tabControl = new CAdminTabControl('tabControl', $this->arTabs);
            $tabControl->Begin();
            echo '<form name="' . str_replace('.', '_', $this->module_id) . '" method="POST" action="' . $APPLICATION->GetCurPage() . '?mid=' . $this->module_id . '&lang=' . LANGUAGE_ID . '" enctype="multipart/form-data">' . bitrix_sessid_post();
            foreach ($arP as $tab => $groups) {
                $tabControl->BeginNextTab();

                foreach ($groups as $group_id => $group) {
                    if (sizeof($group['OPTIONS_SORT']) > 0) {
                        echo '<tr class="heading"><td colspan="2">' . $this->arGroups[$group_id]['TITLE'] . '</td></tr>';

                        array_multisort($group['OPTIONS_SORT'], $group['OPTIONS']);
                        foreach ($group['OPTIONS'] as $opt)
                            echo $opt;
                    }
                }
            }

            if ($this->need_access_tab) {
                $tabControl->BeginNextTab();
                $module_id = $this->module_id;
                require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
            }

            $tabControl->Buttons(); ?>

                <input type="hidden" name="update" value="Y">
                <input type="submit" class="adm-btn-save" name="save" value="<? echo Loc::GetMessage('BIT_METRICA_SAVE'); ?>">
            </form>

            <?php $tabControl->End();
        }
    }
}