<?php
    require_once('../weblap.php');
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title></title>

        <!-- jQuery -->
    	<script src="js/jquery-latest.min.js"></script>
    	<script src="js/jquery-ui.min.js"></script>

    	<!-- Demo stuff -->
    	<link class="ui-theme" rel="stylesheet" href="css/jquery-ui.min.css">
    	<link rel="stylesheet" href="css/bootstrap-v3.min.css">
    	<link rel="stylesheet" href="css/jq.css">
    	<link href="css/prettify.css" rel="stylesheet">
    	<script src="js/prettify.js"></script>
    	<script src="js/docs.js"></script>
    	<style>
        	/* override jQuery UI overriding Bootstrap */
        	.accordion .ui-widget-content a {
        		color: #337ab7;
        	}
    	</style>

    	<!-- Tablesorter: theme -->
    	<link class="theme" rel="stylesheet" href="css/theme.blue.css">
    	<link rel="stylesheet" href="css/dragtable.mod.css">

    	<!-- Tablesorter script: required -->
    	<script src="js/jquery.tablesorter.js"></script>
    	<script src="js/jquery.tablesorter.widgets.js"></script>

    	<script src="js/jquery.dragtable.mod.js"></script>

        <style media="screen">
            /* optional styling */
            caption {
            /* override bootstrap adding 8px to the top & bottom of the caption */
            padding: 0;
            }
            .ui-sortable-placeholder {
            /* change placeholder (seen while dragging) background color */
            background: #ddd;
            }
            div.table-handle-disabled {
            /* optional red background color indicating a disabled drag handle */
            background-color: rgba(255,128,128,0.5);
            /* opacity set to zero for disabled handles in the dragtable.mod.css file */
            opacity: 0.7;
            }
            /* fix cursor */
            .tablesorter-blue .tablesorter-header {
            cursor: default;
            }
            .sorter {
            cursor: pointer;
            }
        </style>
        <script type="text/javascript">
            $(function () {
                $('table').dragtable({
                // *** new dragtable mod options ***
                // this option MUST match the tablesorter selectorSort option!
                sortClass: '.sorter',
                // this function is called after everything has been updated
                tablesorterComplete: function(table){},

                // *** original dragtable settings (non-default) ***
                dragaccept: '.drag-enable',  // class name of draggable cols -> default null = all columns draggable

                // *** original dragtable settings (default values) ***
                revert: false,               // smooth revert
                dragHandle: '.table-handle', // handle for moving cols, if not exists the whole 'th' is the handle
                maxMovingRows: 40,           // 1 -> only header. 40 row should be enough, the rest is usually not in the viewport
                excludeFooter: false,        // excludes the footer row(s) while moving other columns. Make sense if there is a footer with a colspan. */
                onlyHeaderThreshold: 100,    // TODO:  not implemented yet, switch automatically between entire col moving / only header moving
                persistState: null,          // url or function -> plug in your custom persistState function right here. function call is persistState(originalTable)
                restoreState: null,          // JSON-Object or function:  some kind of experimental aka Quick-Hack TODO: do it better
                exact: true,                 // removes pixels, so that the overlay table width fits exactly the original table width
                clickDelay: 10,              // ms to wait before rendering sortable list and delegating click event
                containment: null,           // @see http://api.jqueryui.com/sortable/#option-containment, use it if you want to move in 2 dimesnions (together with axis: null)
                cursor: 'move',              // @see http://api.jqueryui.com/sortable/#option-cursor
                cursorAt: false,             // @see http://api.jqueryui.com/sortable/#option-cursorAt
                distance: 0,                 // @see http://api.jqueryui.com/sortable/#option-distance, for immediate feedback use "0"
                tolerance: 'pointer',        // @see http://api.jqueryui.com/sortable/#option-tolerance
                axis: 'x',                   // @see http://api.jqueryui.com/sortable/#option-axis, Only vertical moving is allowed. Use 'x' or null. Use this in conjunction with the 'containment' setting
                beforeStart: $.noop,         // returning FALSE will stop the execution chain.
                beforeMoving: $.noop,
                beforeReorganize: $.noop,
                beforeStop: $.noop
                })
                // initialize tablesorter
                .tablesorter({
                    theme: 'blue',
                    // this option MUST match the dragtable sortClass option!
                    selectorSort: '.sorter',
                    widgets: ['zebra', 'filter', 'columns', 'resizable'],
                    widgetOptions: {
                        resizable: true,
                        resizable_widths : [ '20%', '20%', '20%', '20%', '20%', '20%' ]
                    }
                });
            });
        </script>
    </head>
    <body>
        <table class="tablesorter" style="width: 120rem;">
            <thead>
                <tr>
                <th class="drag-enable">Teljes név</th> <!-- disable dragtable on this column -->
                <th class="drag-enable">Telefon</th>
                <th class="drag-enable">E-mail</th>
                <th class="drag-enable">Beosztás</th>
                <th class="drag-enable">Részleg</th>
                <th class="drag-enable">Aktív</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>Teljes név</th>
                    <th>Telefon</th>
                    <th>E-mail</th>
                    <th>Beosztás</th>
                    <th>Részleg</th>
                    <th>Aktív</th>
                </tr>
            </tfoot>
            <tbody>
                <?php
                    $felhasznalok = $adatbazis_kapcsolat->s('*', 'login', '', '', '50');

                    if( $felhasznalok ) {
                        foreach($felhasznalok as $felhasznalo) {
                            echo '<tr>
                                <td>'.$felhasznalo['teljes_nev'].'</td>
                                <td>'.$felhasznalo['usertel'].'</td>
                                <td>'.$felhasznalo['usermail'].'</td>
                                <td>'.erteklista(18, $felhasznalo['beosztas_id']).'</td>
                                <td>'.erteklista(17, $felhasznalo['reszleg_id']).'</td>
                                <td>'.($felhasznalo['aktiv'] ? 'igen' : 'nem').'</td>
                            </tr>';
                        }
                    }
                ?>
            </tbody>
        </table>
    </body>
</html>
