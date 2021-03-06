<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * GET SOCIAL WALL POSTS
 */
function social_wall_controller($page) {
	if ($page > 3) {
		return [
			'error' => 'More than 4 pages (starting on page 0) are not allowed'
		];
	}

	if ( !isset($page) ) {
		$page = 0;
	}

	// init page posts
	$page_posts = [];
		

	/************ DU POSTS **********/

	$du_posts_max_count = 6;
	$du_posts = get_posts([
		'post_type' => 'post',
		'posts_per_page' => $du_posts_max_count,
		'paged' => $page + 1,
		'category_name' => 'du-beitrag',
		'orderby' => 'date',
		'order' => 'DESC',
	]);

	foreach ($du_posts as $du_post) {
		array_push($page_posts, get_post_data($du_post));
	}
    

	/*********** FLOCKLER ***********/

	$flockler_posts_configs = [
		'flockler_best_rated' => [
			'next_index' => 0,
			'query' => [
				'post_type' => FLOCKLER_POST_TYPE_NAME,
				'posts_per_page' => -1,
				'meta_key' => 'rating_value',
				'orderby' => 'meta_value_num',
				'order' => 'DESC'
			]
		],
		'flockler_worst_rated' => [
			'next_index' => 0,
			'query' => [
				'post_type' => FLOCKLER_POST_TYPE_NAME,
				'posts_per_page' => -1,
				'meta_key' => 'rating_value',
				'orderby' => 'meta_value_num',
				'order' => 'ASC'
			]
		],
		'flockler_controversial' => [
			'next_index' => 0,
			'query' => [
				'post_type' => FLOCKLER_POST_TYPE_NAME,
				'posts_per_page' => -1,
				'meta_key' => 'rating_value',
				'orderby' => 'meta_value_count',
				'order' => 'DESC',
				'meta_query' => [[
					'key'     => 'rating_value',
					'value'   => [-1, 1],
					'compare' => 'BETWEEN',
				]]
			]
		],
		'flockler_newests' => [
			'next_index' => 0,
			'query' => [
				'post_type' => FLOCKLER_POST_TYPE_NAME,
				'posts_per_page' => -1,
				'order' => 'DESC',
				'orderby' => 'date'
			]
		]
	];

    $flockler_posts_per_page = 39 - 3 - 3 - 2 - ($page_posts ? count($page_posts) : 0); // 3 acts, 3 questions, 2 forms, (max 6) du_posts
	$flockler_max_count = $flockler_posts_per_page * ($page + 1);
	$flockler_posts_count = 0;
	$flockler_post_ids = [];
	$flockler_posts = [];

	// get all flockler posts
	foreach ( $flockler_posts_configs as $key => $config ) {
		$flockler_posts_configs[$key]['posts'] = get_posts($config['query']);
	}

	// get number of available flockler posts
	$available_flockler_posts = sizeof($flockler_posts_configs['flockler_newests']['posts']);

	// loop over all flockler posts until the max count is reached
	while (
		$flockler_posts_count < $available_flockler_posts &&
		$flockler_posts_count < $flockler_max_count
	) {
		// looper over all order types
		foreach ( $flockler_posts_configs as $key => $config ) {
			// get current post and increase next index
			if (!array_key_exists($config['next_index'], $config['posts'])) {
				continue;
			}
			$post = $config['posts'][$config['next_index']];
			$flockler_posts_configs[$key]['next_index'] += 1;

			// validation
			if ( $flockler_posts_count >= $flockler_max_count ) { break; }
			if ( !in_array($post->ID, $flockler_post_ids) ) {
				// get post meta fields
				$post_data = get_post_data($post);
				$post_data['order_type'] = $key;

				// push post and post id
				array_push($flockler_posts, $post_data);
				array_push($flockler_post_ids, $post->ID);
				
				// increase post count
				$flockler_posts_count += 1;
			}
		}
	}

	$flockler_page_posts = array_slice($flockler_posts, -1 * $flockler_posts_per_page, $flockler_posts_per_page);


	// shuffle flockler- and du-posts
	$page_posts = array_merge($page_posts, $flockler_page_posts);
	shuffle($page_posts);


	/************* ACTS *************/

	$acts = [
		'one'   => get_posts([ 'post_type' => 'post', 'category_name' => 'act-one'   ])[0],
		'two'   => get_posts([ 'post_type' => 'post', 'category_name' => 'act-two'   ])[0],
		'three' => get_posts([ 'post_type' => 'post', 'category_name' => 'act-three' ])[0]
	];

	// insert acts with meta fields at index 0, 1/3 and 2/3
	$posts_count = $page_posts ? count($page_posts) : 0;
	array_splice( $page_posts, 0, 						    0, [ get_post_data($acts['one'])   ]);
	array_splice( $page_posts, floor($posts_count / 3), 	0, [ get_post_data($acts['two'])   ]);
	array_splice( $page_posts, floor($posts_count / 3 * 2), 0, [ get_post_data($acts['three']) ]);


	/*********** QUESTION ***********/
	
	$questions = get_posts([
		'post_type' => 'post',
		'posts_per_page' => 3,
		'category_name' => 'frage',
		'orderby' => 'date',
		'order' => 'DESC',
    ]);
    
    $question = get_post_data($questions[0]);

    // insert question at position 4, middle, -4
	array_splice( $page_posts, 4, 0, [ $question ]);
	array_splice( $page_posts, ceil(($page_posts ? count($page_posts) : 0) / 2), 0, [ $question ]);
	array_splice( $page_posts, -4, 0, [ $question ]);
    

	/************* FORM *************/

    // insert 2 forms starting from the end
    $form_number = 2;
    $interval = floor(($page_posts ? count($page_posts) : 0) / $form_number);
    for ($i = 0; $i < $form_number; $i++) { 
        $form = [ 'post_type' => 'user_submitted_posts_form' ];
        array_splice( $page_posts, -1 * ($interval * $i + 1), 0, [ $form ]);
    }

	return json_encode([
        'post_count' => $page_posts ? count($page_posts) : 0,
        'post_types' => array_map(function($post) {
            return $post['post_type'];
        }, $page_posts),
        'data' => $page_posts,
        'error' => null
    ]);
}