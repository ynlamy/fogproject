<?php
/**
 * Group management page
 *
 * PHP version 5
 *
 * The group represented to the GUI
 *
 * @category GroupManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Group management page
 *
 * The group represented to the GUI
 *
 * @category GroupManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class GroupManagementPage extends FOGPage
{
    private static $_common = [];
    /**
     * The node that uses this class
     *
     * @var string
     */
    public $node = 'group';
    /**
     * Initializes the group page
     *
     * @param string $name the name to construct with
     *
     * @return void
     */
    public function __construct($name = '')
    {
        $this->name = 'Group Management';
        parent::__construct($this->name);
        global $id;
        if ($id) {
            $this->_getHostCommon();
        }
        $this->headerData = [
            _('Name'),
            _('Members')
        ];
        $this->templates = [
            '',
            ''
        ];
        $this->attributes = [
            [],
            [
                'width' => 5
            ]
        ];
    }
    /**
     * Get host common items
     *
     * @return void
     */
    private function _getHostCommon()
    {
        $HostCount = $this->obj->getHostCount();
        $hostids = $this->obj->get('hosts');
        $getItems = [
            'imageID',
            'productKey',
            'printerLevel',
            'useAD',
            'enforce',
            'ADDomain',
            'ADOU',
            'ADUser',
            'ADPass',
            'biosexit',
            'efiexit',
        ];
        foreach ($getItems as &$idField) {
            $tmp = self::getClass('HostManager')
                ->distinct(
                    $idField,
                    ['id' => $hostids]
                );
            self::$_common[] = (bool)($tmp == 1);
            unset($idField);
        }
        self::$Host = new Host(max($hostids));
    }
    /**
     * Create a new group.
     *
     * @return void
     */
    public function add()
    {
        $this->title = _('Create New Group');
        // Check all the post fields if they've already been set.
        $group = filter_input(INPUT_POST, 'group');
        $description = filter_input(INPUT_POST, 'description');
        $kern = filter_input(INPUT_POST, 'kern');
        $args = filter_input(INPUT_POST, 'args');
        $init = filter_input(INPUT_POST, 'init');
        $dev = filter_input(INPUT_POST, 'dev');

        // The fields to display
        $fields = [
            '<label class="col-sm-2 control-label" for="group">'
            . _('Group Name')
            . '</label>' => '<input type="text" name="group" '
            . 'value="'
            . $group
            . '" class="groupname-input form-control" '
            . 'id="group" required/>',
            '<label class="col-sm-2 control-label" for="description">'
            . _('Group Description')
            . '</label>' => '<textarea class="form-control" style="resize:vertical;'
            . 'min-height: 50px;" '
            . 'id="description" name="description">'
            . $description
            . '</textarea>',
            '<label class="col-sm-2 control-label" for="kern">'
            . _('Group Product Key')
            . '</label>' => '<input type="text" name="kern" '
            . 'value="'
            . $kern
            . '" class="form-control" id="kern"/>',
            '<label class="col-sm-2 control-label" for="args">'
            . _('Group Kernel Arguments')
            . '</label>' => '<input type="text" name="args" id="args" value="'
            . $args
            . '" class="form-control"/>',
            '<label class="col-sm-2 control-label" for="init">'
            . _('Group Init')
            . '</label>' => '<input type="text" name="init" value="'
            . $init
            . '" id="init" class="form-control"/>',
            '<label class="col-sm-2 control-label" for="dev">'
            . _('Group Primary Disk')
            . '</label>' => '<input type="text" name="dev" value="'
            . $dev
            . '" id="dev" class="form-control"/>'
        ];
        self::$HookManager
            ->processEvent(
                'GROUP_ADD_FIELDS',
                [
                    'fields' => &$fields,
                    'Group' => self::getClass('Group')
                ]
            );
        $rendered = self::formFields($fields);
        unset($fields);
        echo '<div class="box box-solid" id="group-create">';
        echo '<form id="group-create-form" class="form-horizontal" method="post" action="'
            . $this->formAction
            . '" novalidate>';
        echo '<div class="box-body">';
        echo '<!-- Group General -->';
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h3 class="box-title">';
        echo _('Create New Group');
        echo '</h3>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<button class="btn btn-primary" id="send">'
            . _('Create')
            . '</button>';
        echo '</form>';
        echo '</div>';
    }
    /**
     * When submitted to add post this is what's run
     *
     * @return void
     */
    public function addPost()
    {
        header('Content-type: application/json');
        self::$HookManager->processEvent('GROUP_ADD_POST');
        $group = trim(
            filter_input(INPUT_POST, 'group')
        );
        $desc = trim(
            filter_input(INPUT_POST, 'description')
        );
        $kern = trim(
            filter_input(INPUT_POST, 'kern')
        );
        $args = trim(
            filter_input(INPUT_POST, 'args')
        );
        $init = trim(
            filter_input(INPUT_POST, 'init')
        );
        $dev = trim(
            filter_input(INPUT_POST, 'dev')
        );
        try {
            if (!$group) {
                throw new Exception(
                    _('A group name is required!')
                );
            }
            if (self::getClass('GroupManager')->exists($group)) {
                throw new Exception(
                    _('A group already exists with this name!')
                );
            }
            $Group = self::getClass('Group')
                ->set('name', $group)
                ->set('description', $desc)
                ->set('kernel', $kern)
                ->set('kernelArgs', $args)
                ->set('kernelDevice', $dev)
                ->set('init', $init);
            if (!$Group->save()) {
                $serverFault = true;
                throw new Exception(_('Add group failed!'));
            }
            $hook = 'GROUP_ADD_SUCCESS';
            $msg = json_encode(
                [
                    'msg' => _('Group added!'),
                    'title' => _('Group Create Success')
                ]
            );
        } catch (Exception $e) {
            http_response_code(($serverFault) ? 500 : 400);
            $hook = 'GROUP_ADD_FAIL';
            $msg = json_encode(
                [
                    'error' => $e->getMessage(),
                    'title' => _('Group Create Fail')
                ]
            );
        }
        self::$HookManager
            ->processEvent(
                $hook,
                ['Group' => &$Group]
            );
        unset($Group);
        echo $msg;
        exit;
    }
    /**
     * Displays the group general tab.
     *
     * @return void
     */
    public function groupGeneral()
    {
        list(
            $imageIDs,
            $groupKey,
            $printerLevel,
            $aduse,
            $enforcetest,
            $adDomain,
            $adOU,
            $adUser,
            $adPass,
            $biosExit,
            $efiExit
        ) = self::$_common;
        $exitNorm = Service::buildExitSelector(
            'bootTypeExit',
            (
                filter_input(INPUT_POST, 'bootTypeExit') ?: (
                    $biosExit ?
                    self::$Host->get('biosexit') :
                    ''
                )
            ),
            true,
            'bootTypeExit'
        );
        $exitEfi = Service::buildExitSelector(
            'efiBootTypeExit',
            (
                filter_input(INPUT_POST, 'efiBootTypeExit') ?: (
                    $efiExit ?
                    self::$Host->get('efiexit') :
                    ''
                )
            ),
            true,
            'efiBootTypeExit'
        );
        $group = (
            filter_input(INPUT_POST, 'group') ?: $this->obj->get('name')
        );
        $desc = (
            filter_input(INPUT_POST, 'description') ?: $this->obj->get('description')
        );
        $productKey = (
            filter_input(INPUT_POST, 'key') ?: (
                $groupKey ?
                self::$Host->get('productKey') :
                ''
            )
        );
        $productKeytest = self::aesdecrypt($productKey);
        if ($test_base64 = base64_decode($productKeytest)) {
            if (mb_detect_encoding($test_base64, 'utf-8', true)) {
                $productKey = $test_base64;
            }
        } elseif (mb_detect_encoding($productKeytest, 'utf-8', true)) {
            $productKey = $productKeytest;
        }
        $kern = (
            filter_input(INPUT_POST, 'kern') ?: (
                $kern ?
                self::$Host->get('kernel') :
                $this->obj->get('kernel')
            )
        );
        $args = (
            filter_input(INPUT_POST, 'args') ?: (
                $args ?
                self::$Host->get('kernelArgs') :
                $this->obj->get('kernelArgs')
            )
        );
        $init = (
            filter_input(INPUT_POST, 'init') ?: (
                $init ?
                self::$Host->get('init') :
                $this->obj->get('init')
            )
        );
        $dev = (
            filter_input(INPUT_POST, 'dev') ?: (
                $dev ?
                self::$Host->get('kernelDevice')
                : $this->obj->get('kernelDevice')
            )
        );
        $fields = [
            '<label for="name" class="col-sm-2 control-label">'
            . _('Group Name')
            . '</label>' => '<input id="name" class="form-control" placeholder="'
            . _('Group Name')
            . '" type="text" value="'
            . $group
            . '" name="group" required>',
            '<label for="description" class="col-sm-2 control-label">'
            . _('Group Description')
            . '</label>' => '<textarea style="resize:vertical;'
            . 'min-height:50px;" id="description" name="description" class="form-control">'
            . $desc
            . '</textarea>',
            '<label for="productKey" class="col-sm-2 control-label">'
            . _('Group Product Key')
            . '</label>' => '<input id="productKey" name="key" class="form-control" '
            . 'value="'
            . $productKey
            . '" maxlength="29" exactlength="25">',
            '<label for="kern" class="col-sm-2 control-label">'
            . _('Group Kernel')
            . '</label>' => '<input id="kern" name="kern" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $kern
            . '">',
            '<label for="args" class="col-sm-2 control-label">'
            . _('Group Kernel Arguments')
            . '</label>' => '<input id="args" name="args" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $args
            . '">',
            '<label for="init" class="col-sm-2 control-label">'
            . _('Group Init')
            . '</label>' => '<input id="init" name="init" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $init
            . '">',
            '<label for="dev" class="col-sm-2 control-label">'
            . _('Group Primary Disk')
            . '</label>' => '<input id="dev" name="dev" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $dev
            . '">',
            '<label for="bootTypeExit" class="col-sm-2 control-label">'
            . _('Group Bios Exit Type')
            . '</label>' => $exitNorm,
            '<label for="efiBootTypeExit" class="col-sm-2 control-label">'
            . _('Group EFI Exit Type')
            . '</label>' => $exitEfi
        ];
        self::$HookManager->processEvent(
            'GROUP_EDIT_FIELDS',
            [
                'fields' => &$fields,
                'obj' => &$this->obj
            ]
        );
        $rendered = self::formFields($fields);
        echo '<div class="box box-solid">';
        echo '<form id="group-general-form" class="form-horizontal" method="post" action="'
            . self::makeTabUpdateURL('group-general', $this->obj->get('id'))
            . '" novalidate>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<button class="btn btn-primary" id="general-send">' . _('Update') . '</button>';
        echo '<button class="btn btn-danger pull-right" id="general-delete">' . _('Delete') . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }
    /**
     * Group general post element
     *
     * @return void
     */
    public function groupGeneralPost()
    {
        $group = trim(
            filter_input(INPUT_POST, 'group')
        );
        $desc = trim(
            filter_input(INPUT_POST, 'description')
        );
        $key = strtoupper(
            trim(
                filter_input(INPUT_POST, 'key')
            )
        );
        $productKey = preg_replace(
            '/([\w+]{5})/',
            '$1-',
            str_replace(
                '-',
                '',
                $key
            )
        );
        $productKey = substr($productKey, 0, 29);
        $kern = trim(
            filter_input(INPUT_POST, 'kern')
        );
        $args = trim(
            filter_input(INPUT_POST, 'args')
        );
        $dev = trim(
            filter_input(INPUT_POST, 'dev')
        );
        $init = trim(
            filter_input(INPUT_POST, 'init')
        );
        $bte = trim(
            filter_input(INPUT_POST, 'bootTypeExit')
        );
        $ebte = trim(
            filter_input(INPUT_POST, 'efiBootTypeExit')
        );
        if ($group != $this->obj->get('name')) {
            if (!$this->obj->getManager()->exists($group)) {
                throw new Exception(_('Please use another group name'));
            }
        }
        $this->obj
            ->set('name', $group)
            ->set('description', $desc)
            ->set('kernel', $kern)
            ->set('kernelArgs', $args)
            ->set('kernelDevice', $dev)
            ->set('init', $init);
        self::getClass('HostManager')
            ->update(
                [
                    'id' => $this->obj->get('hosts')
                ],
                '',
                [
                    'kernel' => $kern,
                    'kernelArgs' => $args,
                    'kernelDevice' => $dev,
                    'init' => $init,
                    'efiexit' => $ebte,
                    'biosexit' => $bte,
                    'productKey' => trim($productKey)
                ]
            );
    }
    /**
     * Prints the group image element.
     *
     * @return void
     */
    public function groupImage()
    {
        $imageID = (
            self::$_common[0] ?
            self::$Host->get('imageID') :
            ''
        );
        // Group Images
        $imageSelector = self::getClass('ImageManager')
            ->buildSelectBox($imageID, 'image');
        $fields = [
            '<label for="image" class="col-sm-2 control-label">'
            . _('Group image')
            . '</label>' => $imageSelector
        ];
        self::$HookManager
            ->processEvent(
                'GROUP_IMAGE_FIELDS',
                [
                    'fields' => &$fields,
                    'obj' => &$this->obj
                ]
            );
        $rendered = self::formFields($fields);
        echo '<div class="box box-solid">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Group Image Setting');
        echo '</h4>';
        echo '</div>';
        echo '<div class="box-body">';
        echo '<form id="group-image-form" class="form-horizontal" method="post" action="'
            . self::makeTabUpdateURL('group-image', $this->obj->get('id'))
            . '" novalidate>';
        echo $rendered;
        echo '</form>';
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<button class="btn btn-primary" id="image-send">' . _('Update') . '</button>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Group image post element
     *
     * @return void
     */
    public function groupImagePost()
    {
        $image = trim(
            filter_input(INPUT_POST, 'image')
        );
        $this->obj->addImage($image);
    }
    /**
     * Group active directory post element
     *
     * @return void
     */
    public function groupADPost()
    {
        $useAD = isset($_POST['domain']);
        $domain = trim(
            filter_input(
                INPUT_POST,
                'domainname'
            )
        );
        $ou = trim(
            filter_input(
                INPUT_POST,
                'ou'
            )
        );
        $user = trim(
            filter_input(
                INPUT_POST,
                'domainuser'
            )
        );
        $pass = trim(
            filter_input(
                INPUT_POST,
                'domainpassword'
            )
        );
        $enforce = isset($_POST['enforcesel']);
        $this->obj->setAD(
            $useAD,
            $domain,
            $ou,
            $user,
            $pass,
            $enforce
        );
    }
    /**
     * Group printers display.
     *
     * @return void
     */
    public function groupPrinters()
    {
        $printerLevel = (
            filter_input(INPUT_POST, 'level') ?: (
                self::$_common[2] ?
                self::$Host->get('printerLevel') :
                0
            )
        );

        $props = ' method="post" action="'
            . $this->formAction
            . '&tab=group-printers" ';

        // =========================================================
        // Printer Configuration
        echo '<!-- Printers -->';
        echo '<div class="box-group" id="printers">';
        echo '<div class="box box-info">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Group Printer Configuration');
        echo '</h4>';
        echo '</div>';
        echo '<div id="printerconf" class="">';
        echo '<form id="printer-config-form" class="form-horizontal"' . $props . '>';
        echo '<div class="box-body">';
        echo '<div class="radio">';
        echo '<label for="nolevel" data-toggle="tooltip" data-placement="left" '
            . 'title="'
            . _('This setting turns off all FOG Printer Management')
            . '. '
            . _('Although there are multiple levels already')
            . ' '
            . _('between host and global settings')
            . ', '
            . _('this is just another to ensure safety')
            . '.">';
        echo '<input type="radio" name="level" value="0" '
            . 'id="nolevel"'
            . (
                $printerLevel == 0 ?
                ' checked' :
                ''
            )
            . '/> ';
        echo _('No Printer Management');
        echo '</label>';
        echo '</div>';
        echo '<div class="radio">';
        echo '<label for="addlevel" data-toggle="tooltip" data-placement="left" '
            . 'title="'
            . _(
                'This setting only adds and removes '
                . 'printers that are managed by FOG. '
                . 'If the printer exists in printer '
                . 'management but is not assigned to a '
                . 'host, it will remove the printer if '
                . 'it exists on the unassigned host. '
                . 'It will add printers to the host '
                . 'that are assigned.'
            )
            . '">';
        echo '<input type="radio" name="level" value="1" '
            . 'id="addlevel"'
            . (
                $printerLevel == 1 ?
                ' checked' :
                ''
            )
            . '/> ';
        echo _('FOG Managed Printers');
        echo '</label>';
        echo '</div>';
        echo '<div class="radio">';
        echo '<label for="alllevel" data-toggle="tooltip" data-placement="left" '
            . 'title="'
            . _(
                'This setting will only allow FO GAssigned '
                . 'printers to be added to the host. Any '
                . 'printer that is not assigned will be '
                . 'removed including non-FOG managed printers.'
            )
            . '">';
        echo '<input type="radio" name="level" value="2" '
            . 'id="alllevel"'
            . (
                $printerLevel  == 2 ?
                ' checked' :
                ''
            )
            . '/> ';
        echo _('Only Assigned Printers');
        echo '</label>';
        echo '</div>';
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<button type="submit" name="levelup" class='
            . '"btn btn-primary" id="printer-config-send">'
            . _('Update')
            . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';

        // =========================================================
        // Associated Printers
        $buttons = self::makeButton('printer-default', _('Update default'), 'btn btn-primary', $props);
        $buttons .= self::makeButton('printer-remove', _('Remove selected'), 'btn btn-danger', $props);
        $this->headerData = [
            _('Default'),
            _('Printer Alias'),
            _('Printer Type'),
            _('Printer Associated')
        ];
        $this->templates = [
            '',
            '',
            '',
            ''
        ];
        $this->attributes = [
            [
                'class' => 'col-md-1'
            ],
            [],
            [],
            []
        ];
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Update/Remove printers');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Changes will be automatically saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updateprinters" class="">';
        echo '<div class="box-body">';
        $this->render(12, 'group-printers-table', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Group Printer Post.
     *
     * @return void
     */
    public function groupPrinterPost()
    {
        if (isset($_POST['levelup'])) {
            $level = filter_input(INPUT_POST, 'level');
            self::getClass('HostManager')
                ->update(
                    [
                        'id' => $this->get('hosts'),
                    ],
                    '',
                    ['printerLevel' => $level]
                );
        }
        if (isset($_POST['updateprinters'])) {
            $printers = filter_input_array(
                INPUT_POST,
                [
                    'printer' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $printers = $printers['printer'];
            if (count($printers ?: []) > 0) {
                $this->obj->addPrinter($printers);
            }
        }
        if (isset($_POST['defaultsel'])) {
            $this->obj->updateDefault(
                filter_input(
                    INPUT_POST,
                    'default'
                ),
                isset($_POST['default'])
            );
        }
        if (isset($_POST['printdel'])) {
            $printers = filter_input_array(
                INPUT_POST,
                [
                    'printerRemove' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $printers = $printers['printerRemove'];
            if (count($printers ?: []) > 0) {
                $this->obj->removePrinter($printers);
            }
        }
    }
    /**
     * Group snapins.
     *
     * @return void
     */
    public function groupSnapins()
    {
        $props = ' method="post" action="'
            . $this->formAction
            . '&tab=group-snapins" ';

        echo '<!-- Snapins -->';
        echo '<div class="box-group" id="snapins">';
        // =================================================================
        // Associated Snapins
        $buttons = self::makeButton('snapins-remove', _('Remove selected'), 'btn btn-danger', $props);

        $this->headerData = [
            _('Snapin Name'),
            _('Snapin Created'),
            _('Snapin Associated')
        ];
        $this->templates = [
            '',
            '',
            ''
        ];
        $this->attributes = [
            [],
            [],
            []
        ];

        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Update/Remove Snapins');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Changes will be automatically saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updatesnapins" class="">';
        echo '<div class="box-body">';
        $this->render(12, 'group-snapins-table', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Group snapin post
     *
     * @return void
     */
    public function groupSnapinPost()
    {
        if (isset($_POST['updatesnapins'])) {
            $snapins = filter_input_array(
                INPUT_POST,
                [
                    'snapin' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $snapins = $snapins['snapin'];
            if (count($snapins ?: []) > 0) {
                $this->obj->addSnapin($snapins);
            }
        }
        if (isset($_POST['snapdel'])) {
            $snapins = filter_input_array(
                INPUT_POST,
                [
                    'snapinRemove' => [
                        'flags' => FILTER_REQUIRE_ARRAY
                    ]
                ]
            );
            $snapins = $snapins['snapinRemove'];
            if (count($snapins ?: []) > 0) {
                $this->obj->removeSnapin($snapins);
            }
        }
    }
    /**
     * Display's the group service stuff
     *
     * @return void
     */
    public function groupService()
    {
        $props = ' method="post" action="'
            . $this->formAction
            . '&tab=group-service" ';
        echo '<!-- Modules/Service Settings -->';
        echo '<div class="box-group" id="modules">';
        // =============================================================
        // Associated Modules
        // Buttons for this.
        $buttons = self::makeButton(
            'modules-update',
            _('Update'),
            'btn btn-primary',
            $props
        );
        $buttons .= self::makeButton(
            'modules-enable',
            _('Enable All'),
            'btn btn-success',
            $props
        );
        $buttons .= self::makeButton(
            'modules-disable',
            _('Disable All'),
            'btn btn-danger',
            $props
        );
        $this->headerData = [
            _('Module Name'),
            _('Module Associated')
        ];
        $this->templates = [
            '',
            ''
        ];
        $this->attributes = [
            [],
            []
        ];
        // Modules Enable/Disable/Selected
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Group module settings');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Modules disabled globally cannot be enabled here');
        echo '<br/>';
        echo _('Changes will automatically be saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updatemodules" class="">';
        echo '<div class="box-body">';
        echo $this->render(12, 'modules-to-update', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        // Display Manager Element.
        list(
            $r,
            $x,
            $y
        ) = self::getSubObjectIDs(
            'Service',
            [
                'name' => [
                    'FOG_CLIENT_DISPLAYMANAGER_R',
                    'FOG_CLIENT_DISPLAYMANAGER_X',
                    'FOG_CLIENT_DISPLAYMANAGER_Y'
                ]
            ],
            'value'
        );
        $names = [
            'x' => [
                'width',
                _('Screen Width (in pixels)')
            ],
            'y' => [
                'height',
                _('Screen Height (in pixels)')
            ],
            'r' => [
                'refresh',
                _('Screen Refresh Rate (in Hz)')
            ]
        ];
        foreach ($names as $name => &$get) {
            switch ($name) {
            case 'r':
                $val = $r;
                break;
            case 'x':
                $val = $x;
                break;
            case 'y':
                $val = $y;
                break;
            }
            $fields[
                '<label for="'
                . $name
                . '" class="col-sm-2 control-label">'
                . $get[1]
                . '</label>'
            ] = '<input type="text" id="'
            . $name
            . '" class="form-control" name="'
            . $name
            . '" value="'
            . $val
            . '"/>';
            unset($get);
        }
        $rendered = self::formFields($fields);
        unset($fields);
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '&tab=group-service">';
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Display Manager Settings');
        echo '</h4>';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<button class="btn btn-primary" id="displayman-send">'
            . _('Update')
            . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</form>';

        // Auto Log Out
        $tme = filter_input(INPUT_POST, 'tme');
        if (!$tme) {
            $tme = self::getSetting('FOG_CLIENT_AUTOLOGOFF_MIN');
        }
        $fields = [
            '<label for="tme" class="col-sm-2 control-label">'
            . _('Auto Logout Time (in minutes)')
            . '</label>' => '<input type="text" name="tme" class="form-control" '
            . 'value="'
            . $tme
            . '" id="tme"/>'
        ];
        $rendered = self::formFields($fields);
        unset($fields);
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '&tab=group-service">';
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Auto Logout Settings');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Minimum time limit for Auto Logout to become active is 5 minutes.');
        echo '</p>';
        echo '</div>';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '</div>';
        echo '<div class="box-body">';
        echo $rendered;
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<button class="btn btn-primary" id="alo-send">'
            . _('Update')
            . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</form>';
        // End Box Group
        echo '</div>';
    }
    /**
     * The group edit display method
     *
     * @return void
     */
    public function edit()
    {
        list(
            $imageIDs,
            $groupKey,
            $printerLevel,
            $aduse,
            $enforcetest,
            $adDomain,
            $adOU,
            $adUser,
            $adPass,
            $biosExit,
            $efiExit
        ) = self::$_common;
        $hostids = $this->obj->get('hosts');
        self::$Host = new Host(@max($hostids));
        echo '<input type="hidden" name="hostID" value="'
            . self::$Host->get('id')
            . '"/>';
        // Set Field Information
        $printerLevel = (
            $printerLevel ?
            self::$Host->get('printerLevel') :
            ''
        );
        $useAD = (
            $aduse ?
            self::$Host->get('useAD') :
            ''
        );
        $enforce = (
            $enforcetest ?
            self::$Host->get('enforce') :
            ''
        );
        $ADDomain = (
            $adDomain ?
            self::$Host->get('ADDomain') :
            ''
        );
        $ADOU = (
            $adOU ?
            self::$Host->get('ADOU') :
            ''
        );
        $ADUser = (
            $adUser ?
            self::$Host->get('ADUser') :
            ''
        );
        $adPass = (
            $adPass ?
            self::$Host->get('ADPass') :
            ''
        );
        $ADPass = self::$Host->get('ADPass');

        $this->title = sprintf(
            '%s: %s',
            _('Edit'),
            $this->obj->get('name')
        );

        $tabData = array();

        // General
        $tabData[] = array(
            'name' => _('General'),
            'id' => 'group-general',
            'generator' => function() {
                $this->groupGeneral();
            }
        );

        // Image
        $tabData[] = array(
            'name' => _('Image'),
            'id' => 'group-image',
            'generator' => function() {
                $this->groupImage();
            }
        );

        // Active Directory
        $tabData[] = array(
            'name' => _('Active Directory'),
            'id' => 'group-active-directory',
            'generator' => function() {
                $this->adFieldsToDisplay(
                    $useAD,
                    $ADDomain,
                    $ADOU,
                    $ADUser,
                    $ADPass,
                    $enforce
                );
            }
        );

        // Tasks
        $tabData[] = array(
            'name' => _('Tasks'),
            'id' => 'group-tasks',
            'generator' => function() {
                $this->basictasksOptions();
            }
        );

        // Printers
        $tabData[] = array(
            'name' => _('Printers'),
            'id' => 'group-printers',
            'generator' => function() {
                $this->groupPrinters();
            }
        );

        // Snapins
        $tabData[] = array(
            'name' => _('Snapins'),
            'id' => 'group-snapins',
            'generator' => function() {
                $this->groupSnapins();
            }
        );

        // Service
        $tabData[] = array(
            'name' => _('Service Settings'),
            'id' => 'group-service',
            'generator' => function() {
                $this->groupService();
            }
        );

        // Power Management
        /*$tabData[] = [
            'name' => _('Power Management'),
            'id' => 'group-powermanagement',
            'generator' => function() {
                $this->groupPMDisplay();
            }
        ];

        // Inventory
        $tabData[] = [
            'name' => _('Inventory'),
            'id' => 'group-inventory',
            'generator' => function() {
                $this->groupInventory();
            }
        ];*/
        echo self::tabFields($tabData);
    }
    /**
     * Display inventory page, separated as groups can contain
     * a lot of information
     *
     * @return void
     */
    public function inventory()
    {
        $this->title = sprintf(
            '%s %s',
            _('Group'),
            self::$foglang['Inventory']
        );
        echo '<div class="col-xs-9">';
        echo '<div class="tab-pane fade in active">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo $this->title;
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        echo '<div class="text-center">';
        printf(
            $this->reportString,
            sprintf(
                'Group_%s_InventoryReport',
                $this->obj->get('name')
            ),
            _('Export CSV'),
            _('Export CSV'),
            self::$csvfile,
            sprintf(
                'Group_%s_InventoryReport',
                $this->obj->get('name')
            ),
            _('Export PDF'),
            _('Export PDF'),
            self::$pdffile
        );
        echo '</div>';
        $this->ReportMaker = self::getClass('ReportMaker');
        foreach (self::$inventoryCsvHead as $csvHeader => &$classGet) {
            $this->ReportMaker->addCSVCell($csvHeader);
            unset($classGet, $csvHeader);
        }
        $this->ReportMaker->endCSVLine();
        $this->headerData = array(
            _('Host name'),
            _('Memory'),
            _('System Product'),
            _('System Serial')
        );
        $this->templates = array(
            '${host_name}<br/><small>${host_mac}</small>',
            '${memory}',
            '${sysprod}',
            '${sysser}'
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
        );
        Route::listem(
            'host',
            'name',
            false,
            array('id' => $this->obj->get('hosts'))
        );
        $Hosts = json_decode(
            Route::getData()
        );
        $Hosts = $Hosts->hosts;
        foreach ((array)$Hosts as &$Host) {
            if (!$Host->inventory->id) {
                continue;
            }
            $Image = $Host->image;
            $this->data[] = array(
                'host_name' => $Host->name,
                'host_mac' => $Host->primac,
                'memory' => $Host->inventory->mem,
                'sysprod' => $Host->inventory->sysproduct,
                'sysser' => $Host->inventory->sysserial,
            );
            foreach (self::$inventoryCsvHead as $csvHead => &$classGet) {
                switch ($csvHead) {
                case _('Host ID'):
                    $this->ReportMaker->addCSVCell(
                        $Host->id
                    );
                    break;
                case _('Host name'):
                    $this->ReportMaker->addCSVCell(
                        $Host->name
                    );
                    break;
                case _('Host MAC'):
                    $this->ReportMaker->addCSVCell(
                        $Host->mac
                    );
                    break;
                case _('Host Desc'):
                    $this->ReportMaker->addCSVCell(
                        $Host->description
                    );
                    break;
                case _('Host Memory'):
                    $this->ReportMaker->addCSVCell(
                        $Host->inventory->mem
                    );
                    break;
                default:
                    $this->ReportMaker->addCSVCell(
                        $Host->inventory->$classGet
                    );
                    break;
                }
                unset($classGet, $csvHead);
            }
            $this->ReportMaker->endCSVLine();
            unset($Host, $index);
        }
        unset($Hosts);
        $this->ReportMaker->appendHTML($this->process(12));
        //$this->ReportMaker->outputReport(false);
        $_SESSION['foglastreport'] = serialize($this->ReportMaker);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Submit the edit function.
     *
     * @return void
     */
    public function editPost()
    {
        self::$HookManager
            ->processEvent(
                'GROUP_EDIT_POST',
                array('Group' => &$this->obj)
            );
        try {
            global $tab;
            $hostids = $this->obj->get('hosts');
            $name = trim(
                filter_input(INPUT_POST, 'name')
            );
            $desc = trim(
                filter_input(INPUT_POST, 'description')
            );
            $kern = trim(
                filter_input(INPUT_POST, 'kern')
            );
            $args = trim(
                filter_input(INPUT_POST, 'args')
            );
            $dev = trim(
                filter_input(INPUT_POST, 'dev')
            );
            $key = filter_input(INPUT_POST, 'key');
            $useAD = isset($_POST['domain']);
            $items = filter_input_array(
                INPUT_POST,
                array(
                    'printers' => array(
                        'flags' => FILTER_REQUIRE_ARRAY
                    ),
                    'snapins' => array(
                        'flags' => FILTER_REQUIRE_ARRAY
                    ),
                    'modules' => array(
                        'flags' => FILTER_REQUIRE_ARRAY
                    )
                )
            );
            $printers = $items['printers'];
            $snapins = $items['snapins'];
            $modules = $items['modules'];
            $level = filter_input(INPUT_POST, 'level');
            $default = filter_input(INPUT_POST, 'default');
            $x1 = filter_input(INPUT_POST, 'x');
            $y1 = filter_input(INPUT_POST, 'y');
            $r1 = filter_input(INPUT_POST, 'r');
            $time1 = filter_input(INPUT_POST, 'tme');
            $onDemand = (int)isset($_POST['onDemand']);
            $min = filter_input(INPUT_POST, 'scheduleCronMin');
            $hour = filter_input(INPUT_POST, 'scheduleCronHour');
            $dom = filter_input(INPUT_POST, 'scheduleCronDOM');
            $month = filter_input(INPUT_POST, 'scheduleCronMonth');
            $dow = filter_input(INPUT_POST, 'scheduleCronDOW');
            $action = filter_input(INPUT_POST, 'action');
            switch ($tab) {
            case 'group-general':
                $this->groupGeneralPost();
                break;
            case 'group-image':
                $this->groupImagePost();
                break;
            case 'group-active-directory':
                $this->groupADPost();
                break;
            case 'group-printers':
                $this->groupPrinterPost();
                break;
            case 'group-snapins':
                $this->groupSnapinPost();
                break;
            case 'group-service':
                list(
                    $time,
                    $r,
                    $x,
                    $y
                ) = self::getSubObjectIDs(
                    'Service',
                    array(
                        'name' => array(
                            'FOG_CLIENT_AUTOLOGOFF_MIN',
                            'FOG_CLIENT_DISPLAYMANAGER_R',
                            'FOG_CLIENT_DISPLAYMANAGER_X',
                            'FOG_CLIENT_DISPLAYMANAGER_Y'
                        )
                    ),
                    'value'
                );
                $x = (
                    is_numeric($x1) ?
                    $x1 :
                    $x
                );
                $y = (
                    is_numeric($y1) ?
                    $y1 :
                    $y
                );
                $r = (
                    is_numeric($r1) ?
                    $r1 :
                    $r
                );
                $time = (
                    is_numeric($time1) ?
                    $time1 :
                    $time
                );
                $mods = self::getSubObjectIDs('Module');
                $modOn = array_intersect(
                    (array)$mods,
                    (array)$modules
                );
                $modOff = array_diff(
                    (array)$mods,
                    (array)$modOn
                );
                $this->obj
                    ->addModule($modOn)
                    ->removeModule($modOff)
                    ->setDisp($x, $y, $r)
                    ->setAlo($time);
                break;
            case 'group-powermanagement':
                if (!$action) {
                    throw new Exception(_('You must select an action to perform'));
                }
                $items = array();
                if (isset($_POST['pmsubmit'])) {
                    if ($onDemand && $action === 'wol') {
                        $this->obj->wakeOnLAN();
                        break;
                    }
                    $hostIDs = (array)$this->obj->get('hosts');
                    $items = array();
                    foreach ((array)$hostIDs as &$hostID) {
                        $items[] = array(
                            $hostID,
                            $min,
                            $hour,
                            $dom,
                            $month,
                            $dow,
                            $onDemand,
                            $action
                        );
                        unset($hostID);
                    }
                    $fields = array(
                        'hostID',
                        'min',
                        'hour',
                        'dom',
                        'month',
                        'dow',
                        'onDemand',
                        'action'
                    );
                    if (count($items) > 0) {
                        self::getClass('PowerManagementManager')
                            ->insertBatch($fields, $items);
                    }
                }
                break;
            }
            if (!$this->obj->save()) {
                throw new Exception(_('Group update failed!'));
            }
            $hook = 'GROUP_EDIT_SUCCESS';
            $msg = json_encode(
                array(
                    'msg' => _('Group updated!'),
                    'title' => _('Group Update Success')
                )
            );
        } catch (Exception $e) {
            http_response_code(400);
            $hook = 'GROUP_EDIT_FAIL';
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Group Update Fail')
                )
            );
        }
        self::$HookManager
            ->processEvent(
                $hook,
                array('Group' => &$this->obj)
            );
        echo $msg;
        exit;
    }
    /**
     * Presents the printers list table.
     *
     * @return void
     */
    public function getPrintersList()
    {
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        // Workable queries
        $printersSqlStr = "SELECT `%s`,"
            . "IF(`paHostID` is NULL OR `paHostID` = '0' OR `paHostID` = '','dissociated', 'associated') AS `paHostID`,`paIsDefault`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `printerAssoc`
            ON `printers`.`pID` = `printerAssoc`.`paPrinterID`
            AND `hosts`.`hostID` = `printerAssoc`.`paHostID`
            %s
            %s
            %s";

        $printersFilterStr = "SELECT COUNT(`%s`),"
            . "IF(`paHostID` IS NULL OR `paHostID` = '0' OR `paHostID` = '', 'dissociated', 'associated') AS `paHostID`,`paIsDefault`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `printerAssoc`
            ON `printers`.`pID` = `printerAssoc`.`paPrinterID`
            AND `hosts`.`hostID` = `printerAssoc`.`paHostID`
            %s";

        $printersTotalStr = "SELECT COUNT(`%s`)
            FROM `%s`";

        foreach (self::getClass('PrinterManager')
            ->getColumns() as $common => &$real
        ) {
            $columns[] = array('db' => $real, 'dt' => $common);
            unset($real);
        }
        $columns[] = array('db' => 'paIsDefault', 'dt' => 'isDefault');
        $columns[] = array('db' => 'paHostID', 'dt' => 'association');
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                'printers',
                'pID',
                $columns,
                $printersSqlStr,
                $printersFilterStr,
                $printersTotalStr,
                $where
            )
        );
        exit;
    }
    /**
     * Presents the snapins list table.
     *
     * @return void
     */
    public function getSnapinsList()
    {
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        // Workable queries
        $snapinsSqlStr = "SELECT `%s`,"
            . "IF(`saHostID` is NULL OR `saHostID` = '0' OR `saHostID` = '','dissociated', 'associated') AS `saHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `snapinAssoc`
            ON `snapins`.`sID` = `snapinAssoc`.`saSnapinID`
            AND `hosts`.`hostID` = `snapinAssoc`.`saHostID`
            %s
            %s
            %s";

        $snapinsFilterStr = "SELECT COUNT(`%s`),"
            . "IF(`saHostID` IS NULL OR `saHostID` = '0' OR `saHostID` = '', 'dissociated', 'associated') AS `saHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `snapinAssoc`
            ON `snapins`.`sID` = `snapinAssoc`.`saSnapinID`
            AND `hosts`.`hostID` = `snapinAssoc`.`saHostID`
            %s";

        $snapinsTotalStr = "SELECT COUNT(`%s`)
            FROM `%s`";

        foreach (self::getClass('SnapinManager')
            ->getColumns() as $common => &$real
        ) {
            $columns[] = array('db' => $real, 'dt' => $common);
            unset($real);
        }
        $columns[] = array('db' => 'saHostID', 'dt' => 'association');
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                'snapins',
                'sID',
                $columns,
                $snapinsSqlStr,
                $snapinsFilterStr,
                $snapinsTotalStr,
                $where
            )
        );
        exit;
    }
    /**
     * Returns the module list as well as the associated
     * for the group being edited.
     *
     * @return void
     */
    public function getModulesList()
    {
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $moduleName = self::getGlobalModuleStatus();
        $keys = [];
        foreach ((array)$moduleName as $short_name => $bool) {
            if ($bool) {
                $keys[] = $short_name;
            }
        }

        $where = "`modules`.`short_name` NOT IN('clientupdater','dircleanup','greenfog','usercleanup') "
            . "AND `modules`.`short_name` IN ('" . implode("','", $keys) . "')";

        /*$where = "`hosts`.`hostID` = '"
            . $this->obj->get('id')
            . "' AND `modules`.`short_name` "
            . "NOT IN ('clientupdater','dircleanup','greenfog','usercleanup') ";*/

        // Workable queries
        $modulesSqlStr = "SELECT `%s`,"
            . "IF(`msHostID` IS NULL OR `msHostID` = '0' OR `msHostID` = '', 'dissociated', 'associated') AS `msHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `moduleStatusByHost`
            ON `modules`.`id` = `moduleStatusByHost`.`msModuleID`
            AND `hosts`.`hostID` = `moduleStatusByHost`.`msHostID`
            %s
            %s
            %s";

        $modulesFilterStr = "SELECT COUNT(`%s`),"
            . "IF(`msHostID` IS NULL OR `msHostID` = '0' OR `msHostID` = '', 'dissociated', 'associated') AS `msHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `moduleStatusByHost`
            ON `modules`.`id` = `moduleStatusByHost`.`msModuleID`
            AND `hosts`.`hostID` = `moduleStatusByHost`.`msHostID`
            %s";

        $modulesTotalStr = "SELECT COUNT(`%s`)
            FROM `%s` WHERE `modules`.`short_name`
            NOT IN ('clientupdater','dircleanup','greenfog','usercleanup')";

        foreach (self::getClass('ModuleManager')
            ->getColumns() as $common => &$real
        ) {
            $columns[] = array('db' => $real, 'dt' => $common);
            unset($real);
        }
        $columns[] = array('db' => 'msHostID', 'dt' => 'association');
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                'modules',
                'id',
                $columns,
                $modulesSqlStr,
                $modulesFilterStr,
                $modulesTotalStr,
                $where
            )
        );
        exit;
    }
    /**
     * Display the group PM stuff.
     *
     * @return void
     */
    public function groupPMDisplay()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        echo '<!-- Power Management Items -->';
        echo '<div class="tab-pane fade" id="group-powermanagement">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('Power Management');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        $this->newPMDisplay();
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('Group Power Management Remove');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        echo '<label for="delAllPM" class="col-xs-4">'
            . _('Delete all PM tasks?')
            . '</label>';
        echo '<div class="col-xs-8">';
        echo '<button id="delAllPM" type="button" class='
            . '"btn btn-danger btn-block">'
            . _('Delete')
            . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
    }
}
