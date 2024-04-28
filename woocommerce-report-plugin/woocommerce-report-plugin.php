<?php
/*
Plugin Name: WooCommerce Report Plugin
Description: A WooCommerce plugin to provide detailed sales reports.
Version: 1.0
Author: Your Name
*/

// Thêm một trang menu mới vào bảng điều khiển WooCommerce
function woocommerce_report_menu()
{
    add_submenu_page(
        'woocommerce',
        'Sales Report',
        'Sales Report',
        'manage_woocommerce',
        'woocommerce-sales-report',
        'woocommerce_sales_report_page'
    );
}

add_action('admin_menu', 'woocommerce_report_menu');


function load_chart_js_from_cdn()
{
    // Nạp thư viện Chart.js từ CDN
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '2.9.4', true);
}

add_action('admin_enqueue_scripts', 'load_chart_js_from_cdn');

// Hiển thị nội dung trang báo cáo doanh số bán hàng
function woocommerce_sales_report_page()
{
    // Kiểm tra xem WooCommerce có được cài đặt và kích hoạt không
    if (!class_exists('WooCommerce')) {
        echo '<div class="error"><p>This plugin requires WooCommerce to be installed and activated.</p></div>';
        return;
    }

    // Lấy doanh số bán hàng từ cơ sở dữ liệu WooCommerce
    $orders = wc_get_orders(
        array(
            'numberposts' => -1,
            'post_status' => array('wc-completed', 'wc-processing', 'wc-on-hold') // Lấy các đơn hàng hoàn thành, đang xử lý, và tạm giữ
        )
    );

    echo do_shortcode('[display_sales_report]');

    // Kiểm tra xem có đơn hàng nào không
    // Kiểm tra xem có đơn hàng nào không
    if (!empty($orders)) {
        // Tạo một mảng để lưu trữ số lượng đơn hàng đã được đặt và đơn hàng đã giao thành công cho mỗi ngày
        $placed_orders_by_date = array();
        $completed_orders_by_date = array();
        $on_hold_orders_by_date = array();
        $processing_orders_by_date = array();

        foreach ($orders as $order) {
            $order_date = $order->get_date_created()->format('Y-m-d');
            $order_status = $order->get_status();

            // Tăng số lượng đơn hàng cho ngày và trạng thái tương ứng
            if (!isset($placed_orders_by_date[$order_date])) {
                $placed_orders_by_date[$order_date] = 0;
            }
            $placed_orders_by_date[$order_date]++;

            // Tăng số lượng đơn hàng cho từng trạng thái tương ứng
            switch ($order_status) {
                case 'completed':
                    if (!isset($completed_orders_by_date[$order_date])) {
                        $completed_orders_by_date[$order_date] = 0;
                    }
                    $completed_orders_by_date[$order_date]++;
                    break;
                case 'on-hold':
                    if (!isset($on_hold_orders_by_date[$order_date])) {
                        $on_hold_orders_by_date[$order_date] = 0;
                    }
                    $on_hold_orders_by_date[$order_date]++;
                    break;
                case 'processing':
                    if (!isset($processing_orders_by_date[$order_date])) {
                        $processing_orders_by_date[$order_date] = 0;
                    }
                    $processing_orders_by_date[$order_date]++;
                    break;
                default:
                    break;
            }
        }

        // Kiểm tra và thêm ngày nếu cần thiết cho các trạng thái đặc biệt
        foreach ($placed_orders_by_date as $date => $value) {
            if (!isset($completed_orders_by_date[$date])) {
                $completed_orders_by_date[$date] = 0;
            }
            if (!isset($on_hold_orders_by_date[$date])) {
                $on_hold_orders_by_date[$date] = 0;
            }
            if (!isset($processing_orders_by_date[$date])) {
                $processing_orders_by_date[$date] = 0;
            }
        }

        // Sắp xếp các mảng theo ngày
        ksort($placed_orders_by_date);
        ksort($completed_orders_by_date);
        ksort($on_hold_orders_by_date);
        ksort($processing_orders_by_date);

    } else {
        echo '<p>No orders found.</p>'; // Thông báo nếu không có đơn hàng
    }



    // Tạo dữ liệu cho biểu đồ
    $chart_data = array(
        'labels' => array_keys($placed_orders_by_date),
        'placed' => array_values($placed_orders_by_date),
        'completed' => array_values($completed_orders_by_date),
        'on_hold' => array_values($on_hold_orders_by_date),
        'processing' => array_values($processing_orders_by_date) // Thêm dữ liệu cho đơn hàng đang xử lý
    );

    // Hiển thị biểu đồ và thêm dữ liệu cho đơn hàng đang xử lý vào biểu đồ
    echo '<div class="wrap">';
    echo '<h2>Sales Report</h2>';
    echo '<canvas id="sales-chart" width="800" height="400"></canvas>';
    echo '</div>';

    // Script JavaScript để vẽ biểu đồ
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '    var ctx = document.getElementById("sales-chart").getContext("2d");';
    echo '    window.myChart = new Chart(ctx, {'; // Lưu biểu đồ vào window.myChart
    echo '        type: "bar",';
    echo '        data: {';
    echo '            labels: ' . json_encode($chart_data['labels']) . ',';
    echo '            datasets: [';
    echo '                {';
    echo '                    label: "Placed",';
    echo '                    data: ' . json_encode($chart_data['placed']) . ',';
    echo '                    backgroundColor: "rgba(255, 99, 132, 0.2)",';
    echo '                    borderColor: "rgba(255, 99, 132, 1)",';
    echo '                    borderWidth: 1';
    echo '                },';
    echo '                {';
    echo '                    label: "Completed",';
    echo '                    data: ' . json_encode($chart_data['completed']) . ',';
    echo '                    backgroundColor: "rgba(54, 162, 235, 0.2)",';
    echo '                    borderColor: "rgba(54, 162, 235, 1)",';
    echo '                    borderWidth: 1';
    echo '                },';
    echo '                {';
    echo '                    label: "On Hold",';
    echo '                    data: ' . json_encode($chart_data['on_hold']) . ',';
    echo '                    backgroundColor: "rgba(255, 206, 86, 0.2)",';
    echo '                    borderColor: "rgba(255, 206, 86, 1)",';
    echo '                    borderWidth: 1';
    echo '                },';
    echo '                {';
    echo '                    label: "Processing",';
    echo '                    data: ' . json_encode($chart_data['processing']) . ',';
    echo '                    backgroundColor: "rgba(75, 192, 192, 0.2)",';
    echo '                    borderColor: "rgba(75, 192, 192, 1)",';
    echo '                    borderWidth: 1';
    echo '                }'; // Thêm dataset cho đơn hàng đang xử lý
    echo '            ]';
    echo '        },';
    echo '        options: {';
    echo '            scales: {';
    echo '                yAxes: [{';
    echo '                    ticks: {';
    echo '                        beginAtZero: true';
    echo '                    }';
    echo '                }]';
    echo '            }';
    echo '        }';
    echo '    });';
    echo '});';
    echo '</script>';


    // Hiển thị bảng đơn hàng
    echo '<div class="wrap">';
    echo '<h2>Sales Report</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Date</th>';
    echo '<th>Status</th>';
    echo '<th>Total</th>';
    echo '<th>Action</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($orders as $order) {
        $order_id = $order->get_id();
        $order_date = $order->get_date_created()->format('Y-m-d');
        $order_status = $order->get_status();
        $order_total = $order->get_total();

        echo '<tr>';
        echo '<td>' . $order_id . '</td>';
        echo '<td>' . $order_date . '</td>';
        echo '<td>' . $order_status . '</td>';
        echo '<td>' . wc_price($order_total) . '</td>';
        echo '<td><a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '" class="button">View Details</a></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    // Hiển thị thông tin doanh số hàng tháng
    display_monthly_sales_info();

}

function woocommerce_sales_report_page_date()
{

}

function display_monthly_sales_info()
{
    // Get the current month and year
    $current_month = date('m');
    $current_year = date('Y');

    // Calculate the start and end dates of the current month
    $start_date = date('Y-m-01', strtotime("$current_year-$current_month-01"));
    $end_date = date('Y-m-t', strtotime("$current_year-$current_month-01"));

    // Query orders for the current month
    $orders = wc_get_orders(
        array(
            'date_created' => '>=' . $start_date,
            'date_created' => '<=' . $end_date,
            'numberposts' => -1,
            'post_status' => array('wc-completed') // Consider only completed orders
        )
    );

    // Initialize variables
    $total_orders = count($orders);
    $total_sales = 0;

    // Calculate total sales amount
    foreach ($orders as $order) {
        $total_sales += $order->get_total();
    }

    // Calculate average daily sales
    $days_in_month = date('t', strtotime("$current_year-$current_month-01"));
    $average_daily_sales = $total_sales / $days_in_month;

    // Display the information
    echo '<div class="sales-info">';
    echo '<h3>Monthly Sales Information</h3>';
    echo '<p>Total Orders Sold: ' . $total_orders . '</p>';
    echo '<p>Total Sales Amount: ' . wc_price($total_sales) . '</p>';
    echo '<p>Average Daily Sales: ' . wc_price($average_daily_sales) . '</p>';
    echo '</div>';
}

//=============================================================================================================================
// Enqueue necessary scripts and styles
function enqueue_date_picker()
{
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}
add_action('admin_enqueue_scripts', 'enqueue_date_picker');

// Shortcode to display sales report form
function display_sales_report_shortcode()
{
    ob_start();
    ?>
    <!-- HTML for date selection form -->
    <form id="sales-report-form">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date">
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date">
        <input type="submit" value="Display Sales Report">
    </form>
    <!-- Container to display report -->
    <div id="sales-report-container"></div>

    <!-- JavaScript for AJAX -->
    <script>
        jQuery(document).ready(function ($) {
            $('#sales-report-form').submit(function (e) {
                e.preventDefault(); // Prevent default form submission
                var formData = $(this).serialize(); // Get form data

                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>', // URL for AJAX processing
                    data: {
                        action: 'get_sales_report', // Action to determine processing function
                        formData: formData // Form data to send
                    },
                    success: function (response) {
                        // Parse JSON response
                        var data = JSON.parse(response);
                        // Display the report in a list
                        // Now you can use data.chart_data to draw/update the chart using Chart.js
                        var ctx = document.getElementById('sales-chart').getContext('2d');
                        // Hủy biểu đồ cũ nếu đã tồn tại
                        if (window.myChart) {
                            window.myChart.destroy();
                        }
                        // Vẽ biểu đồ mới
                        window.myChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: 'Placed',
                                    data: data.placed,
                                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Completed',
                                    data: data.completed,
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'On Hold',
                                    data: data.on_hold,
                                    backgroundColor: 'rgba(255, 206, 86, 0.5)',
                                    borderColor: 'rgba(255, 206, 86, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Processing',
                                    data: data.processing,
                                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    xAxes: [{
                                        stacked: true
                                    }],
                                    yAxes: [{
                                        stacked: true,
                                        ticks: {
                                            beginAtZero: true
                                        }
                                    }]
                                }
                            }
                        });
                    },

                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('display_sales_report', 'display_sales_report_shortcode');

// AJAX callback function to process form data and generate sales report
add_action('wp_ajax_get_sales_report', 'get_sales_report_callback');
add_action('wp_ajax_nopriv_get_sales_report', 'get_sales_report_callback');

function get_sales_report_callback()
{

    // Kiểm tra xem WooCommerce có được cài đặt và kích hoạt không
    if (!class_exists('WooCommerce')) {
        echo '<div class="error"><p>This plugin requires WooCommerce to be installed and activated.</p></div>';
        return;
    }
    parse_str($_POST['formData'], $formData); // Parse form data
    $start_date = $formData['start_date'];
    $end_date = $formData['end_date'];

    // Lấy doanh số bán hàng từ cơ sở dữ liệu WooCommerce
    $orders = wc_get_orders(
        array(
            'numberposts' => -1,
            'post_status' => array('wc-completed', 'wc-processing', 'wc-on-hold'), // Lấy các đơn hàng hoàn thành, đang xử lý, và tạm giữ
            'date_query' => array(
                array(
                    'after' => $start_date,
                    'before' => $end_date,
                    'inclusive' => true,
                ),
            ),
        )
    );



    // Kiểm tra xem có đơn hàng nào không
    if (!empty($orders)) {
        // Tạo một mảng để lưu trữ số lượng đơn hàng đã được đặt và đơn hàng đã giao thành công cho mỗi ngày
        $placed_orders_by_date = array();
        $completed_orders_by_date = array();
        $on_hold_orders_by_date = array();
        $processing_orders_by_date = array();

        foreach ($orders as $order) {
            $order_date = $order->get_date_created()->format('Y-m-d');
            $order_status = $order->get_status();

            // Tăng số lượng đơn hàng cho ngày và trạng thái tương ứng
            if (!isset($placed_orders_by_date[$order_date])) {
                $placed_orders_by_date[$order_date] = 0;
            }
            $placed_orders_by_date[$order_date]++;

            // Tăng số lượng đơn hàng cho từng trạng thái tương ứng
            switch ($order_status) {
                case 'completed':
                    if (!isset($completed_orders_by_date[$order_date])) {
                        $completed_orders_by_date[$order_date] = 0;
                    }
                    $completed_orders_by_date[$order_date]++;
                    break;
                case 'on-hold':
                    if (!isset($on_hold_orders_by_date[$order_date])) {
                        $on_hold_orders_by_date[$order_date] = 0;
                    }
                    $on_hold_orders_by_date[$order_date]++;
                    break;
                case 'processing':
                    if (!isset($processing_orders_by_date[$order_date])) {
                        $processing_orders_by_date[$order_date] = 0;
                    }
                    $processing_orders_by_date[$order_date]++;
                    break;
                default:
                    break;
            }
        }

        // Kiểm tra và thêm ngày nếu cần thiết cho các trạng thái đặc biệt
        foreach ($placed_orders_by_date as $date => $value) {
            if (!isset($completed_orders_by_date[$date])) {
                $completed_orders_by_date[$date] = 0;
            }
            if (!isset($on_hold_orders_by_date[$date])) {
                $on_hold_orders_by_date[$date] = 0;
            }
            if (!isset($processing_orders_by_date[$date])) {
                $processing_orders_by_date[$date] = 0;
            }
        }

        // Sắp xếp các mảng theo ngày
        ksort($placed_orders_by_date);
        ksort($completed_orders_by_date);
        ksort($on_hold_orders_by_date);
        ksort($processing_orders_by_date);

    } else {
        echo '<p>No orders found.</p>'; // Thông báo nếu không có đơn hàng
    }

    // Tạo dữ liệu cho biểu đồ
    $chart_data = array(
        'labels' => array_keys($placed_orders_by_date),
        'placed' => array_values($placed_orders_by_date),
        'completed' => array_values($completed_orders_by_date),
        'on_hold' => array_values($on_hold_orders_by_date),
        'processing' => array_values($processing_orders_by_date) // Thêm dữ liệu cho đơn hàng đang xử lý
    );



    // Generate sales report based on selected date range (You need to implement this part)
    // For demonstration purpose, let's just display the selected date range
    // Gán nội dung của biến $sales_report

    echo json_encode($chart_data);
    wp_die();
}
