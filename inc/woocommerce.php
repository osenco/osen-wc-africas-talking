<?php
require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';

add_action('woocommerce_order_status_pending_to_processing_notification', 'iconic_processing_notification', 10, 1);
function iconic_processing_notification($order_id)
{

    $order      = wc_get_order($order_id);
    $total      = round($order->get_total());
    $phone      = $order->get_billing_phone();
    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $reference  = 'ORDER#' . $order_id;

    $username = at_option('username');
    $apiKey   = at_option('key');
    $AT       = new AfricasTalking\SDK\AfricasTalking($username, $apiKey);
    $sms      = $AT->sms();

    $recipients = $order->get_billing_phone();
    $message    = "Hi {$first_name} {$last_name}, your order {$reference} of KSh {$total} has been received and being processed.";
    $from       = "myShortCode";

    try {
        // Thats it, hit send and we'll take care of the rest
        $result = $sms->send([
            'to'      => $recipients,
            'message' => $message,
            'from'    => $from,
        ]);
    } catch (Exception $e) {
        $result = [
            'status' => 'error',
            'data'   => $e->getMessage(),
        ];
    }

    return $result;
}

add_action('woocommerce_thankyou_africastalking', 'wc_ati_add_content_thankyou_africastalking');
function wc_ati_add_content_thankyou_africastalking($order_id)
{
    $order = wc_get_order($order_id);

    if ($order->get_payment_method() !== 'africastalking') {
        return;
    }?>

	<style>
		@keyframes wave {
			0%, 60%, 100% {
				transform: initial;
			}

			30% {
				transform: translateY(-15px);
			}
		}

		@keyframes blink {
			0% {
				opacity: .2;
			}

			20% {
				opacity: 1;
			}

			100% {
				opacity: .2;
			}
		}

		.saving span {
			animation: blink 1.4s linear infinite;
			animation-fill-mode: both;
		}

		.saving span:nth-child(2) {
			animation-delay: .2s;
		}

		.saving span:nth-child(3) {
			animation-delay: .4s;
		}
	</style>
	<section class="woocommerce-order-details africastalking">
		<input type="hidden" id="current_order" value="<?php echo $order_id; ?>">
		<input type="hidden" id="payment_method" value="<?php echo $order->get_payment_method(); ?>">
		<p class="saving" id="africastalking_receipt">Confirming receipt, please wait</p>
	</section><?php
}

add_action('wp_footer', 'ati_ajax_polling');
function ati_ajax_polling()
{?>
	<script id="atipn_atichecker">
		var atichecker = setInterval(() => {
			if (document.getElementById("payment_method") !== null && document.getElementById("payment_method").value !== 'africastalking') {
				clearInterval(atichecker);
			}

			jQuery(function($) {
				var order = $("#current_order").val();
				if (order !== undefined || order !== '') {
					$.get('<?php echo home_url('?atipncheck&order='); ?>' + order, [], function(data) {
						if (data.receipt == '' || data.receipt == 'N/A') {
							$("#africastalking_receipt").html('Confirming payment <span>.</span><span>.</span><span>.</span><span>.</span><span>.</span><span>.</span>');
						} else {
							$(".woocommerce-order-overview").append('<li class="woocommerce-order-overview__payment-method method">Receipt number: <strong>' + data.receipt + '</strong></li>');
							$(".woocommerce-table--order-details > tfoot").find('tr:last-child').prev().after('<tr><th scope="row">Receipt number:</th><td>' + data.receipt +'</td></tr>');
							$("#africastalking_receipt").html('Payment confirmed. Receipt number: <b>' + data.receipt + '</b>');
							clearInterval(atichecker);
							return false;
						}
					})
				}
			});
		}, 3000);
	</script><?php
}
