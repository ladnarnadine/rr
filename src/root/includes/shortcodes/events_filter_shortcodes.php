<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Event filter inputs
 */
add_shortcode( 'events_filter', 'events_filter_func' );
function events_filter_func( $attributes, $content ){
    setlocale(LC_TIME, "de_DE");

    // categories
    $category_data = query_options_data('category');
    $categories = [];
    foreach ($category_data as $post) {
        $post_id = $post->ID;
        
        $raw_categories = wp_get_post_terms($post_id, 'event_category');
        $_categories = array_map(function($category) {
            $taxonomy_id = $category->taxonomy . '_' . $category->term_id;
            return [
                'value' => $category->slug,
                'label' => $category->name,
                'order' =>  get_field('order', $taxonomy_id)
            ];
        }, $raw_categories);

        if (!empty($_categories)) {
            $categories = array_merge($categories, $_categories);
        }
    }
    $categories_unique = array_unique($categories, SORT_REGULAR);
    usort($categories_unique, function($a, $b) {
        return floatval($a['order']) - floatval($b['order']);
    });
    $category_options = generate_options($categories_unique);
    
    // artists
    $artist_data = query_options_data('artist');
    $artists = [];
    foreach ($artist_data as $post) {
        $post_id = $post->ID;

        $artist = get_field('artist', $post_id);
        if ($artist) {
            array_push($artists, $artist);
        }
    }
    natsort($artists);
    $artist_options = generate_options(array_unique($artists));
    
    
    // cities
    $city_data = query_options_data('city');
    $cities = [];
    foreach ($city_data as $post) {
        $post_id = $post->ID;

        $city = get_field('city', $post_id);
        if ($city) {
            array_push($cities, $city);
        }
    }
    natsort($cities);
    $city_options = generate_options(array_unique($cities));


    // dates
    $date_data = query_options_data('date');
    $dates = [];
    foreach ($date_data as $post) {
        $post_id = $post->ID;

        $date = get_field('date', $post_id);
        if ($date) {
            $timestamp = strtotime($date);
            $date_formatted = utf8_encode(strftime("%b %G", $timestamp)); // e.g. 'Jan. 2019'
            $date_key = date("Ym", $timestamp); // e.g. '201901'
            $dates[$date_key] = ['value' => $date_key, 'label' => $date_formatted];
        }
    }
    $date_options = generate_options($dates);

    return "
        <form action='/activism/#activism' id='events-filter'>
            <div class='input-group'>
                <label for='event-filter-categories'>Nach Thema filtern</label>
                <select name='category' id='event-filter-categories'>
                    <option value=''>Alle Themen</option>
                    $category_options
                </select>
            </div>
            <div class='input-group'>
                <label for='event-filter-artists'>Nach Aktivist filtern</label>
                <select name='artist' id='event-filter-artists'>
                    <option value=''>Alle Aktivisten</option>
                    $artist_options
                </select>
            </div>
            <div class='input-group'>
                <label for='event-filter-cities'>Nach Ort filtern</label>
                <select name='city' id='event-filter-cities'>
                    <option value=''>Alle Orte</option>
                    $city_options
                </select>
            </div>
            <div class='input-group'>
                <label for='event-filter-dates'>Nach Datum filtern</label>
                <select name='date' id='event-filter-dates'>
                    <option value=''>Alle Daten</option>
                    $date_options
                </select>
            </div>
        </form>
    ";
}


/**
 * Query events based on the GEt parameters
 * but ignore the paramater for the current
 * select input name
 */
$memorized_queries = [];
function query_options_data($name) {
    $query_key = '';

    // base query
    $query = [
        'post_type' => 'activism',
        'posts_per_page' => -1,
        'meta_key' => 'date',
        'meta_type' => 'DATE',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => []
    ];

    // filter by category
    if ($name !== 'category' && isset($_GET['category']) && $_GET['category'] !== '') {
        $query['tax_query'] = [[
            'taxonomy' => 'event_category',
            'field'    => 'slug',
            'terms'    => $_GET['category']
        ]];
        $query_key .= 'category';
    }

    // filter by artist
    if ($name !== 'artist' && isset($_GET['artist']) && $_GET['artist'] !== '') {
        $query['meta_query'][] = [
            'key' => 'artist',
            'value' => $_GET['artist'],
            'compare' => '='
        ];
        $query_key .= 'artist';
    }

    // filter by city
    if ($name !== 'city' && isset($_GET['city']) && $_GET['city'] !== '') {
        $query['meta_query'][] = [
            'key' => 'city',
            'value' => $_GET['city'],
            'compare' => '='
        ];
        $query_key .= 'city';
    }

    // filter by date
    if ($name !== 'date' && isset($_GET['date']) && $_GET['date'] !== '') {
        $date_regex = $_GET['date'] . '[0-9]{1,2}';
        $query['meta_query'][] = [
            'key' => 'date',
            'value' => $date_regex,
            'compare' => 'REGEXP'
        ];
        $query_key .= 'date';
    }

    // return memorized query results or query results from database
    if ($memorized_queries[$query_key]) {
        return $memorized_queries[$query_key];
    } else {
        $query_results = get_posts($query);
        $memorized_queries[$query_key] = $query_results;
        return $query_results;
    }
}


/**
 * Generate html option string helper function
 */
function generate_options($option_data)  {
    $options = '';
    foreach ($option_data as $option) {
        $value = is_array($option) ? $option['value'] : $option;
        $label = is_array($option) ? $option['label'] : ucfirst($option);

        $options .=  "<option value='$value'>$label</option>";
    }
    return $options;
}
