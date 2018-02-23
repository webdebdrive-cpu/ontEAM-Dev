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
                        resizable_widths : [ '10%', '10%', '10%', '10%', '10%', '5%', '5%' ]
                    }
                });
            });
        </script>
    </head>
    <body>
        <table class="tablesorter">
            <caption>Student Grades</caption>
            <thead>
                <tr>
                <th class="drag-enable">Name (0)</th> <!-- disable dragtable on this column -->
                <th class="drag-enable">Major (1)</th>
                <th class="drag-enable">Sex (2)</th>
                <th class="drag-enable">English (3)</th>
                <th class="drag-enable">Japanese (4)</th>
                <th class="drag-enable">Calculus (5)</th>
                <th class="drag-enable">Geometry (6)</th>
                </tr>
            </thead>
            <tfoot>
                <tr><th>Name</th><th>Major</th><th>Sex</th><th>English</th><th>Japanese</th><th>Calculus</th><th>Geometry</th></tr>
            </tfoot>
            <tbody>
                <tr><td>Student01 (0)</td><td>Languages (1)</td><td>male (2)</td><td>80 (3)</td><td>70 (4)</td><td>75 (5)</td><td>80 (6)</td></tr>
                <tr><td>Student02</td><td>Mathematics</td><td>male</td><td>90</td><td>88</td><td>100</td><td>90</td></tr>
                <tr><td>Student03</td><td>Languages</td><td>female</td><td>85</td><td>95</td><td>80</td><td>85</td></tr>
                <tr><td>Student04</td><td>Languages</td><td>male</td><td>60</td><td>55</td><td>100</td><td>100</td></tr>
                <tr><td>Student05</td><td>Languages</td><td>female</td><td>68</td><td>80</td><td>95</td><td>80</td></tr>
                <tr><td>Student06</td><td>Mathematics</td><td>male</td><td>100</td><td>99</td><td>100</td><td>90</td></tr>
                <tr><td>Student07</td><td>Mathematics</td><td>male</td><td>85</td><td>68</td><td>90</td><td>90</td></tr>
                <tr><td>Student08</td><td>Languages</td><td>male</td><td>100</td><td>90</td><td>90</td><td>85</td></tr>
                <tr><td>Student09</td><td>Mathematics</td><td>male</td><td>80</td><td>50</td><td>65</td><td>75</td></tr>
                <tr><td>Student10</td><td>Languages</td><td>male</td><td>85</td><td>100</td><td>100</td><td>90</td></tr>
                <tr><td>Student11</td><td>Languages</td><td>male</td><td>86</td><td>85</td><td>100</td><td>100</td></tr>
                <tr><td>Student12</td><td>Mathematics</td><td>female</td><td>100</td><td>75</td><td>70</td><td>85</td></tr>
                <tr><td>Student13</td><td>Languages</td><td>female</td><td>100</td><td>80</td><td>100</td><td>90</td></tr>
                <tr><td>Student14</td><td>Languages</td><td>female</td><td>50</td><td>45</td><td>55</td><td>90</td></tr>
                <tr><td>Student15</td><td>Languages</td><td>male</td><td>95</td><td>35</td><td>100</td><td>90</td></tr>
                <tr><td>Student16</td><td>Languages</td><td>female</td><td>100</td><td>50</td><td>30</td><td>70</td></tr>
                <tr><td>Student17</td><td>Languages</td><td>female</td><td>80</td><td>100</td><td>55</td><td>65</td></tr>
                <tr><td>Student18</td><td>Mathematics</td><td>male</td><td>30</td><td>49</td><td>55</td><td>75</td></tr>
                <tr><td>Student19</td><td>Languages</td><td>male</td><td>68</td><td>90</td><td>88</td><td>70</td></tr>
                <tr><td>Student20</td><td>Mathematics</td><td>male</td><td>40</td><td>45</td><td>40</td><td>80</td></tr>
            </tbody>
        </table>
    </body>
</html>
