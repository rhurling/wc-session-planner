<?php

/**
 * Plugin Name: WordCamp Session Planner
 * Version: 1.0
 * Author: Rouven Hurling
 * Author URI: http://rhurling.de
 * License: GPL2
 */
class WordCamp_Session_Planner {

	function __construct() {
		add_shortcode( 'session-planner', array( $this, 'shortcode' ) );
	}

	function shortcode( $attr, $content ) {
		$query_args = array(
			'post_type'      => 'wcb_session',
			'posts_per_page' => - 1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_wcpt_session_time',
					'compare' => 'EXISTS',
				),
			),
		);

		$sessions       = array();
		$sessions_query = new WP_Query( $query_args );
		foreach ( $sessions_query->posts as $session ) {
			$time = absint( get_post_meta( $session->ID, '_wcpt_session_time', true ) );

			if ( ! isset( $sessions[ $time ] ) ) {
				$sessions[ $time ] = array();
			}

			$sessions[ $time ][] = $session;
		}

		// Sort all sessions by their key (timestamp).
		ksort( $sessions );

		$dates = array();
		foreach ( $sessions as $time => $entries ) {
			$date        = date( 'd.m.Y', strtotime( $time ) );
			$entry_count = count( $entries );
			if ( ! isset( $dates[ $date ] ) ) {
				$dates[ $date ] = array();
			}

			if ( $entry_count > 1 ) {
				$dates[ $date ][ $time ] = false;
			} elseif ( $entry_count > 0 ) {
				$dates[ $date ][ $time ] = reset( $entries );
			}
		}

		$html = '<div class="wscp-container">';

		$html .= $this->get_fillable_tables( $dates );

		$html .= '<div class="schedule">';
		$html .= $this->get_daily_schedules( $dates );
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	function get_fillable_tables( $dates ) {
		$time_format = get_option( 'time_format', 'g:i a' );
		$html        = '';
		foreach ( $dates as $date => $times ) {
			$html .= '<div class="wpsc-tab">';
			$html .= '<h3>' . date( 'l', strtotime( $date ) ) . '</h3>';
			$html .= '<table class="wcpt-schedule" border="0">';
			$html .= '<thead>';
			$html .= '<tr>';
			$html .= '<thead>';
			$html .= '<th class="wcpt-col-time">Time</th>';
			$html .= '<th class="wcpt-col-track">Session</th>';
			$html .= '</tr>';
			$html .= '</thead>';
			$html .= '<tbody>';
			foreach ( $times as $time => $entry ) {
				$html .= sprintf(
					'<tr class="%s">',
					sanitize_html_class( 'wcpt-time-' . date( $time_format, $time ) ) . ' wcsp-fixed-session'
				);
				$html .= sprintf(
					'<td class="wcpt-time">%s</td>',
					str_replace( ' ', '&nbsp;', esc_html( date( $time_format, $time ) ) )
				);
				if ( $entry ) {
					$session_tracks       = get_the_terms( $entry->ID, 'wcb_track' );
					$session_track_titles = implode( ', ', wp_list_pluck( $session_tracks, 'name' ) );
					$html .= sprintf(
						'<td>%s<br/><span class="wpsc-track">%s</span></td>',
						$entry->post_title,
						$session_track_titles
					);
				} else {
					$html .= '<td></td>';
				}
				$html .= '</tr>';
			}
			$html .= '</tbody>';
			$html .= '</table>';
			$html .= '</div>';
		}

		return $html;
	}

	function get_daily_schedules( $dates ) {
		$html = '';
		foreach ( $dates as $date => $times ) {
			$html .= '<h3>' . date( 'l', strtotime( $date ) ) . '</h3>';
			$html .= do_shortcode( '[schedule session_link="none" date="' . $date . '"]' );
		}

		return $html;
	}

}

$GLOBALS['wcsp_plugin'] = new WordCamp_Session_Planner;