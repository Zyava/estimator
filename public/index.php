<?php
include('predef.php');
require 'classes/Loader.php';
require 'classes/Session.php';
Loader::registerAutoload();

include('session.php');
include('header.php');

$sprintObj = new Sprint();
$sprintList = $sprintObj->getSprintList();

// Set new sprint
$sprintId = !empty($_REQUEST['sprint_id']) ? intval($_REQUEST['sprint_id']) : 0;
if ($sprintId > 0) {
    $_SESSION['user_sprint_id'] = $sprintId;
} elseif (empty($_SESSION['user_sprint_id']) && isset($sprintList['data'][0]['id']) > 0) {
    $_SESSION['user_sprint_id'] = $sprintList['data'][0]['id'];
}

$sessionObj = new Session();
$paramsList = $sessionObj->getSessionParams($_SESSION['user_sprint_id']);

?>

    <script type="text/javascript">
        var calcCheckedTasks = new Array();

        function updateCalculationTable() {
            $('#task_calc_table input').unbind('change');

            var tasks = Task.getState();
            var tableText = '';
            var totalEstimate = 0;
            $.each(tasks, function(index, value){

                var id = "";
                if (value.id && value.id.substr(0, 4) == 'card') {
                    id = value.id.substr(4);
                } else {
                    return;
                }

                var contNum = value.container;
                var estimate = 0;
                if (contNum && contNum.substr(0, 6) == 'column') {
                    estimate = parseInt(contNum.substr(6));
                }

                var checkedStatus = '';
                if (calcCheckedTasks[id] === undefined) {
                    if (value.status == 1) {
                        checkedStatus = ' checked = "true"';
                        calcCheckedTasks[id] = true;
                    }
                } else {
                    if (calcCheckedTasks[id] === true) {
                        checkedStatus = ' checked = "true"';
                    }
                }

                if (calcCheckedTasks[id] === true) {
                    totalEstimate += estimate;
                }

                tableText += '<tr' + (estimate ? ' class="highlight"' : '') + '>';
                tableText += '<td><input type="checkbox" id="calcTask' + id + '" value="' + estimate + '"'
                    + checkedStatus + ' /></td>';
                tableText += '<td><label for="calcTask' + id + '">' + value.title + '</label></td>';
                tableText += '<td><label for="calcTask' + id + '">' + value.description + '</label></td>';
                tableText += '<td align="right">' + estimate + '</td>';
                tableText += '</tr>';
            });

            tableText += '<tr>';
            tableText += '<th colspan="3" align="right">Total estimate: </th>';
            tableText += '<th align="left" class="total">' + totalEstimate + '</th>';
            tableText += '</tr>';

            $('#task_calc_table').html(tableText);
            $('#task_calc_table tr').hover(
                function () {
                    $(this).addClass('hover');
                },
                function () {
                    $(this).removeClass('hover');
                }
            );

            $('#task_calc_table input').change(function() {
                var id = $(this).attr('id').substr(8);
                if ($(this).attr('checked')) {
                    calcCheckedTasks[id] = true;
                    $(this).parent().parent().addClass('highlight');
                } else {
                    calcCheckedTasks[id] = false;
                    $(this).parent().parent().removeClass('highlight');
                }
                var status = calcCheckedTasks[id] ? 1 : 0;
                sendToSocket({'class' : 'TicketController', 'method' : 'onStatusChange',
                    'data' : {'id' : id, 'status' : status}});
                updateTotalCalculation();
            });
        }

        function updateTotalCalculation() {
            var totalEstimate = 0;
            $.each($('#task_calc_table input:checked'), function(index, value){
                totalEstimate += parseInt($(this).val().substr(0, 8));
            });
            $('#task_calc_table th.total').text(totalEstimate);
        }

        $(function() {
            connectSocket('<?php echo session_id() ?>', '<?php echo $_SESSION['user_sprint_id'] ?>');

            // Tabs
            $('#tabs').tabs();

            <? if ($_SESSION['user_role'] == ROLE_ADMIN) { ?>
            $('#tabs').bind( "tabsselect", function(event, ui) {
                sendToSocket({'class' : 'TabController', 'method' : 'onChangeCurrentTab',
                    'data' : {'tab_index' : ui.index}});
                updateCalculationTable();
            });
            <? } else { ?>
            $('#tabs').bind( "tabsselect", function(event, ui) {
                if (ui.index == 2) {
                    updateCalculationTable();
                }
            });
            <? } ?>

            $('#sprint-list-select').change(function() {
                $('#sprint-list-select').attr('disable', 'true');
                sendToSocket({'class' : 'SprintController', 'method' : 'onChangeSprint',
                    'data' : {'new_sprint_id' : $('#sprint-list-select').val()}});
            });

            var cards = [
                {
                    id: 'column000',
                    title: '?',
                    container: 'tabs-2'
                },
                {
                    id: 'column00',
                    title: '?',
                    container: 'tabs-2'
                },
                {
                    id: 'column0',
                    title: '?',
                    container: 'tabs-2'
                },
                {
                    id: 'column1',
                    title: '1',
                    container: 'tabs-2'
                },
                {
                    id: 'column2',
                    title: '2',
                    container: 'tabs-2'
                },
                {
                    id: 'column3',
                    title: '3',
                    container: 'tabs-2'
                },
                {
                    id: 'column5',
                    title: '5',
                    container: 'tabs-2'
                },
                {
                    id: 'column8',
                    title: '8',
                    container: 'tabs-2'
                },
                {
                    id: 'column13',
                    title: '13',
                    container: 'tabs-2'
                },
                {
                    id: 'column20',
                    title: '20',
                    container: 'tabs-2'
                },
                {
                    id: 'column50',
                    title: '50',
                    container: 'tabs-2'
                }
            ];

            $(cards).each(function(index, elem) {
                Card.create(elem);
            });

            Estimator.init(Card.getAll(), Task.getAll());
            Estimator.enable();
        });
    </script>
<?

if ($_SESSION['user_role'] != ROLE_ADMIN) {
    $tabClasses = array_fill(0, 3, ' class="ui-state-disabled"');
    $tabClasses[intval($paramsList['data']['current_tab'])] = ' class="ui-tabs-selected ui-state-active"';
} elseif (isset($paramsList['data']['current_tab'])) {
    $tabClasses = array('', '', '');
    $tabClasses[intval($paramsList['data']['current_tab'])] = ' class="ui-tabs-selected ui-state-active"';
}

?>
    <div id="sprint-list-container">
        <form action="/public/index.php" method="POST" id="sprint-list-form">
            <label for="sprint_id">Sprint: </label><select id="sprint-list-select" name="sprint_id">
            <?php
                if (isset($sprintList['data'])) {
                    foreach ($sprintList['data'] as $sprint) {
                        echo '<option value="'
                            . $sprint['id'] . '"'
                            . (!empty($_SESSION['user_sprint_id'])
                                && $_SESSION['user_sprint_id'] == $sprint['id']
                                ? ' selected = "true"' : "") . '>'
                            . $sprint['sprint_name'] . '</option>';
                    }
                }
            ?>
            </select>
        </form>
    </div>

    <div id="main_buttons">
        <input type="button" value="Create task" onclick="Estimator.showTaskForm();" />
    </div>

    <div id="tabs">
        <ul>
            <li<?=empty($tabClasses[0])?'':$tabClasses[0]?>><a href="#tabs-1">Drag</a></li>
            <li<?=empty($tabClasses[1])?'':$tabClasses[1]?>><a href="#tabs-2">Estimate</a></li>
            <li<?=empty($tabClasses[2])?'':$tabClasses[2]?>><a href="#tabs-3">Calculate</a></li>
        </ul>
        <div id="tabs-1" class="task-container">
            <div id="drag_container" class="drag-container"></div>
        </div>
        <div id="tabs-2" class="task-container"></div>
        <div id="tabs-3">
            <table id="task_calc_table">
            </table>
        </div>
    </div>

    <div id="edit-task" title="Create task" style="display: none;">
        <p id="validate-tips" class="validateTips">All form fields are required.</p>
        <form>
            <fieldset>
                <label for="title">Title</label>
                <input type="text" id="title" class="text ui-widget-content ui-corner-all" />
                <label for="description">Description</label>
                <input type="text" id="description" value="" class="text ui-widget-content ui-corner-all" />
            </fieldset>
        </form>
    </div>
    <div id='logs' class="ui-widget ui-widget-content ui-corner-all">
        <div id='users'></div>
        <div id='log'></div>
    </div>

<? include('footer.php'); ?>
