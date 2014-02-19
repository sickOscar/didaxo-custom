<?php 

namespace TU;

!defined( 'ABSPATH' ) and exit;

add_action( 'woocommerce_thankyou', 'user_bought_lesson' );

/**
 * Hook per acquisto lezione
 * @param  [type] $order_id [description]
 * @return [type]           [description]
 */
function user_bought_lesson( $order_id  )
{
	$order = new WC_Order( $order_id );
	$items = $order->get_items();
	foreach( $items as $item )
	{
		$group_ID = get_post_meta( get_the_ID(), 'wpcf-trainup_group_id', true );
		add_user_meta( tu()->user->ID, 'tu_group', $group_ID );
	}
}
