<?php
/**
 * Template part for displaying pagination
 */
?>

<div class="hph-pagination">
    <?php
    global $wp_query;

    echo paginate_links(array(
        'base'         => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
        'total'        => $wp_query->max_num_pages,
        'current'      => max(1, get_query_var('paged')),
        'format'       => '?paged=%#%',
        'show_all'     => false,
        'type'         => 'list',
        'end_size'     => 2,
        'mid_size'     => 1,
        'prev_next'    => true,
        'prev_text'    => sprintf('<i></i> %1$s', __('Previous', 'happy-place')),
        'next_text'    => sprintf('%1$s <i></i>', __('Next', 'happy-place')),
        'add_args'     => false,
        'add_fragment' => '',
    ));
    ?>
</div>
