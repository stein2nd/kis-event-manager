<?php
/**
 * Plugin Name: KIS イベント管理
 * Description: KISサイト用のセミナー・アーカイブ配信管理機能です。
 * Version: 3.2.3
 * Author: KIS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * 1. イベント投稿タイプ
 * ========================================================= */

function kis_register_event_post_type() {

    $labels = array(
        'name'               => 'イベント',
        'singular_name'      => 'イベント',
        'menu_name'          => 'イベント',
        'name_admin_bar'     => 'イベント',
        'add_new'            => '新規追加',
        'add_new_item'       => '新規イベントを追加',
        'new_item'           => '新規イベント',
        'edit_item'          => 'イベントを編集',
        'view_item'          => 'イベントを表示',
        'all_items'          => 'イベント一覧',
        'search_items'       => 'イベントを検索',
        'not_found'          => 'イベントが見つかりません',
        'not_found_in_trash' => 'ゴミ箱にイベントはありません',
    );

    register_post_type(
        'kis_event',
        array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'query_var'          => true,

            'rewrite' => array(
                'slug' => 'event',
            ),

            'has_archive'   => true,
            'hierarchical'  => false,
            'menu_position' => 5,
            'menu_icon'     => 'dashicons-calendar-alt',

            'supports' => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'revisions',
            ),
        )
    );
}

add_action(
    'init',
    'kis_register_event_post_type'
);


/* =========================================================
 * 2. アイキャッチ画像
 * ========================================================= */

function kis_event_enable_thumbnail_support() {

    add_theme_support(
        'post-thumbnails'
    );
}

add_action(
    'after_setup_theme',
    'kis_event_enable_thumbnail_support'
);


/* =========================================================
 * 3. 日付整形
 * ========================================================= */

function kis_event_format_date( $event_date ) {

    if ( ! $event_date ) {
        return '';
    }

    $timestamp = strtotime( $event_date );

    if ( ! $timestamp ) {
        return '';
    }

    $weekdays = array(
        '日',
        '月',
        '火',
        '水',
        '木',
        '金',
        '土',
    );

    $weekday = $weekdays[
        (int) wp_date(
            'w',
            $timestamp
        )
    ];

    return
        wp_date(
            'Y.m.d',
            $timestamp
        )
        . '（'
        . $weekday
        . '）';
}


/* =========================================================
 * 4. イベント種別取得
 * ========================================================= */

function kis_event_get_type_info( $post_id = null ) {

    if ( ! $post_id ) {
        $post_id = get_the_ID();
    }

    $event_type = get_post_meta(
        $post_id,
        '_kis_event_type',
        true
    );

    if ( 'archive' === $event_type ) {
        return array(
            'name'       => 'アーカイブ配信',
            'slug'       => 'archive',
            'is_archive' => true,
            'is_ended'   => false,
        );
    }

    if ( 'ended' === $event_type ) {
        return array(
            'name'       => '終了',
            'slug'       => 'ended',
            'is_archive' => false,
            'is_ended'   => true,
        );
    }

    return array(
        'name'       => 'セミナー',
        'slug'       => 'seminar',
        'is_archive' => false,
        'is_ended'   => false,
    );
}


/* =========================================================
 * 5. イベント情報入力欄
 * ========================================================= */

function kis_event_add_meta_box() {

    add_meta_box(
        'kis_event_details',
        'イベント情報',
        'kis_event_meta_box_callback',
        'kis_event',
        'normal',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'kis_event_add_meta_box'
);


function kis_event_meta_box_callback( $post ) {

    wp_nonce_field(
        'kis_event_save_meta',
        'kis_event_meta_nonce'
    );

    $event_type = get_post_meta(
        $post->ID,
        '_kis_event_type',
        true
    );

    $event_date = get_post_meta(
        $post->ID,
        '_kis_event_date',
        true
    );

    $event_time = get_post_meta(
        $post->ID,
        '_kis_event_time',
        true
    );

    $event_format = get_post_meta(
        $post->ID,
        '_kis_event_format',
        true
    );

    $event_fee = get_post_meta(
        $post->ID,
        '_kis_event_fee',
        true
    );

    /*
     * 旧バージョンとの互換性のため、
     * 旧URLがあればセミナー申込URLとして読み込みます。
     */
    $seminar_url = get_post_meta(
        $post->ID,
        '_kis_event_seminar_url',
        true
    );

    if ( ! $seminar_url ) {

        $seminar_url = get_post_meta(
            $post->ID,
            '_kis_event_url',
            true
        );
    }

    $youtube_url = get_post_meta(
        $post->ID,
        '_kis_event_youtube_url',
        true
    );

    $event_button = get_post_meta(
        $post->ID,
        '_kis_event_button',
        true
    );

    $archived_at = get_post_meta(
        $post->ID,
        '_kis_event_archived_at',
        true
    );

    if ( ! $event_type ) {
        $event_type = 'seminar';
    }

    ?>

    <div class="kis-admin-event-fields">

        <p>
            <label for="kis_event_type">
                <strong>イベント種別</strong>
            </label>
            <br>

            <select
                id="kis_event_type"
                name="kis_event_type"
                style="width:300px;margin-top:6px;"
            >
                <option
                    value="seminar"
                    <?php selected( $event_type, 'seminar' ); ?>
                >
                    セミナー
                </option>

                <option
                    value="archive"
                    <?php selected( $event_type, 'archive' ); ?>
                >
                    アーカイブ配信
                </option>

                <option
                    value="ended"
                    <?php selected( $event_type, 'ended' ); ?>
                >
                    終了
                </option>
            </select>
        </p>


        <!-- アーカイブ切替時の案内 -->

        <div
            id="kis_archive_message"
            style="
                display:none;
                margin:20px 0;
                padding:14px 16px;
                background:#f0f6fc;
                border-left:4px solid #2271b1;
                line-height:1.7;
            "
        >
            <strong>
                アーカイブ配信として公開します。
            </strong>

            <br>

            元の開催情報は削除されず、
            内部データとして保持されます。

            <?php if ( $event_date ) : ?>

                <br>

                <span style="color:#666;">
                    元の開催日：
                    <?php
                    echo esc_html(
                        kis_event_format_date(
                            $event_date
                        )
                    );
                    ?>
                </span>

            <?php endif; ?>


            <?php if ( $archived_at ) : ?>

                <br>

                <span style="color:#666;">
                    アーカイブ切替日：
                    <?php
                    echo esc_html(
                        wp_date(
                            'Y年n月j日',
                            strtotime(
                                $archived_at
                            )
                        )
                    );
                    ?>
                </span>

            <?php endif; ?>

        </div>


        <hr style="margin:25px 0;">


        <!-- セミナー用入力欄 -->

        <div id="kis_seminar_fields">

            <p>
                <label for="kis_event_date">
                    <strong>開催日</strong>
                </label>
                <br>

                <input
                    type="date"
                    id="kis_event_date"
                    name="kis_event_date"
                    value="<?php echo esc_attr( $event_date ); ?>"
                    style="width:300px;margin-top:6px;"
                >
            </p>


            <p style="margin-top:20px;">
                <label for="kis_event_time">
                    <strong>開催時間</strong>
                </label>
                <br>

                <input
                    type="text"
                    id="kis_event_time"
                    name="kis_event_time"
                    value="<?php echo esc_attr( $event_time ); ?>"
                    placeholder="例：11:00～11:45"
                    style="width:300px;margin-top:6px;"
                >
            </p>


            <p style="margin-top:20px;">
                <label for="kis_event_format">
                    <strong>開催形式</strong>
                </label>
                <br>

                <input
                    type="text"
                    id="kis_event_format"
                    name="kis_event_format"
                    value="<?php echo esc_attr( $event_format ); ?>"
                    placeholder="例：オンライン（Zoom）"
                    style="width:500px;max-width:100%;margin-top:6px;"
                >
            </p>


            <p style="margin-top:20px;">
                <label for="kis_event_fee">
                    <strong>参加費</strong>
                </label>
                <br>

                <input
                    type="text"
                    id="kis_event_fee"
                    name="kis_event_fee"
                    value="<?php echo esc_attr( $event_fee ); ?>"
                    placeholder="例：無料"
                    style="width:300px;margin-top:6px;"
                >
            </p>


            <p style="margin-top:20px;">
                <label for="kis_event_seminar_url">
                    <strong>セミナー申込URL</strong>
                </label>
                <br>

                <input
                    type="url"
                    id="kis_event_seminar_url"
                    name="kis_event_seminar_url"
                    value="<?php echo esc_attr( $seminar_url ); ?>"
                    placeholder="https://..."
                    style="width:700px;max-width:100%;margin-top:6px;"
                >

                <br>

                <span style="color:#666;font-size:12px;">
                    セミナーの申込ページURLを入力してください。
                </span>
            </p>

        </div>


        <!-- アーカイブ用入力欄 -->

        <div
            id="kis_archive_fields"
            style="display:none;"
        >

            <p>
                <label for="kis_event_youtube_url">
                    <strong>YouTube限定公開URL</strong>
                </label>
                <br>

                <input
                    type="url"
                    id="kis_event_youtube_url"
                    name="kis_event_youtube_url"
                    value="<?php echo esc_attr( $youtube_url ); ?>"
                    placeholder="https://youtu.be/..."
                    style="width:700px;max-width:100%;margin-top:6px;"
                >

                <br>

                <span style="color:#b32d2e;font-size:12px;">
                    ※このURLはイベント詳細ページには直接表示しません。
                    視聴申込者への案内に使用します。
                </span>
            </p>

        </div>


        <hr style="margin:25px 0;">


        <p>
            <label for="kis_event_button">
                <strong>ボタンの文言</strong>
            </label>
            <br>

            <input
                type="text"
                id="kis_event_button"
                name="kis_event_button"
                value="<?php echo esc_attr( $event_button ); ?>"
                style="width:400px;max-width:100%;margin-top:6px;"
            >

            <br>

            <span
                id="kis_button_help"
                style="color:#666;font-size:12px;"
            >
            </span>
        </p>

    </div>

    <?php
}


/* =========================================================
 * 6. イベント情報保存
 * ========================================================= */

function kis_event_save_meta( $post_id ) {

    if (
        ! isset(
            $_POST['kis_event_meta_nonce']
        )
    ) {
        return;
    }


    if (
        ! wp_verify_nonce(
            sanitize_text_field(
                wp_unslash(
                    $_POST['kis_event_meta_nonce']
                )
            ),
            'kis_event_save_meta'
        )
    ) {
        return;
    }


    if (
        defined( 'DOING_AUTOSAVE' )
        &&
        DOING_AUTOSAVE
    ) {
        return;
    }


    if (
        ! current_user_can(
            'edit_post',
            $post_id
        )
    ) {
        return;
    }


    $old_event_type = get_post_meta(
        $post_id,
        '_kis_event_type',
        true
    );


    $new_event_type = 'seminar';

    if (
        isset(
            $_POST['kis_event_type']
        )
    ) {

        $new_event_type =
            sanitize_text_field(
                wp_unslash(
                    $_POST['kis_event_type']
                )
            );
    }


    if (
        ! in_array(
            $new_event_type,
            array(
                'seminar',
                'archive',
                'ended',
            ),
            true
        )
    ) {
        $new_event_type = 'seminar';
    }


    /*
     * 初めてアーカイブへ切り替えた日時を保存
     */

    if (
        'archive' === $new_event_type
        &&
        'archive' !== $old_event_type
    ) {

        update_post_meta(
            $post_id,
            '_kis_event_archived_at',
            current_time(
                'mysql'
            )
        );
    }


    update_post_meta(
        $post_id,
        '_kis_event_type',
        $new_event_type
    );


    /*
     * 開催情報はアーカイブ化しても削除しない
     */

    $text_fields = array(

        'kis_event_date'
            => '_kis_event_date',

        'kis_event_time'
            => '_kis_event_time',

        'kis_event_format'
            => '_kis_event_format',

        'kis_event_fee'
            => '_kis_event_fee',

        'kis_event_button'
            => '_kis_event_button',
    );


    foreach (
        $text_fields
        as
        $field_name => $meta_key
    ) {

        if (
            isset(
                $_POST[ $field_name ]
            )
        ) {

            update_post_meta(
                $post_id,
                $meta_key,
                sanitize_text_field(
                    wp_unslash(
                        $_POST[ $field_name ]
                    )
                )
            );
        }
    }


    /*
     * セミナー申込URL
     */

    if (
        isset(
            $_POST['kis_event_seminar_url']
        )
    ) {

        update_post_meta(
            $post_id,
            '_kis_event_seminar_url',
            esc_url_raw(
                wp_unslash(
                    $_POST['kis_event_seminar_url']
                )
            )
        );
    }


    /*
     * YouTube限定公開URL
     */

    if (
        isset(
            $_POST['kis_event_youtube_url']
        )
    ) {

        update_post_meta(
            $post_id,
            '_kis_event_youtube_url',
            esc_url_raw(
                wp_unslash(
                    $_POST['kis_event_youtube_url']
                )
            )
        );
    }
}

add_action(
    'save_post_kis_event',
    'kis_event_save_meta'
);


/* =========================================================
 * 7. CSS読み込み
 * ========================================================= */

function kis_event_enqueue_styles() {

    wp_enqueue_style(
        'kis-event-style',
        plugin_dir_url( __FILE__ )
            . 'css/event.css',
        array(),
        '3.2.3'
    );

    /*
     * 年別タブ用CSS
     * event.cssを差し替えなくても動くよう、
     * プラグイン側から追加しています。
     */
    $tab_css = '
        .kis-event-year-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
            margin: 0 0 42px;
            padding: 0;
            border-bottom: 2px solid #174a91;
        }

        .kis-event-year-tab {
            appearance: none;
            min-width: 120px;
            margin: 0 8px 0 0;
            padding: 13px 24px;
            border: 1px solid #cbd2da;
            border-bottom: 0;
            border-radius: 0;
            background: #fff;
            color: #222;
            font: inherit;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.4;
            cursor: pointer;
            transition:
                background .2s ease,
                color .2s ease,
                border-color .2s ease;
        }

        .kis-event-year-tab:hover {
            border-color: #174a91;
            background: #f4f7fb;
            color: #174a91;
        }

        .kis-event-year-tab.is-active {
            border-color: #174a91;
            background: #174a91;
            color: #fff;
        }

        .kis-event-year-panel {
            display: none;
        }

        .kis-event-year-panel.is-active {
            display: block;
        }

        .kis-event-empty {
            margin: 40px 0;
            color: #666;
        }

        @media screen and (max-width: 600px) {
            .kis-event-year-tabs {
                gap: 8px;
                margin-bottom: 30px;
                border-bottom: 0;
            }

            .kis-event-year-tab {
                flex: 1 1 calc(33.333% - 8px);
                min-width: 88px;
                margin: 0;
                padding: 10px 12px;
                border-bottom: 1px solid #cbd2da;
                font-size: 14px;
            }

            .kis-event-year-tab.is-active {
                border-color: #174a91;
            }
        }
    ';

    wp_add_inline_style(
        'kis-event-style',
        $tab_css
    );
}

add_action(
    'wp_enqueue_scripts',
    'kis_event_enqueue_styles'
);


/* =========================================================
 * 8. 一覧カード
 * ========================================================= */

function kis_event_render_card() {

    $event_date = get_post_meta(
        get_the_ID(),
        '_kis_event_date',
        true
    );

    $event_time = get_post_meta(
        get_the_ID(),
        '_kis_event_time',
        true
    );

    $type = kis_event_get_type_info();

    ?>

    <article class="kis-event-card">

        <a
            href="<?php the_permalink(); ?>"
            class="kis-event-image"
        >

            <?php

            if ( has_post_thumbnail() ) {

                the_post_thumbnail(
                    'large',
                    array(
                        'alt' => get_the_title(),
                    )
                );
            }

            ?>

        </a>


        <div class="kis-event-body">

            <div
                class="kis-event-tag<?php echo $type['is_archive'] ? ' archive' : ( $type['is_ended'] ? ' ended' : '' ); ?>"
            >
                <?php
                echo esc_html(
                    $type['name']
                );
                ?>
            </div>


            <?php
            if (
                ! $type['is_archive']
            ) :
            ?>

                <div class="kis-event-date">

                    <?php

                    echo esc_html(
                        kis_event_format_date(
                            $event_date
                        )
                    );

                    if ( $event_time ) {

                        echo ' ';

                        echo esc_html(
                            $event_time
                        );
                    }

                    ?>

                </div>

            <?php
            endif;
            ?>


            <h2 class="kis-event-title">

                <a
                    href="<?php the_permalink(); ?>"
                >
                    <?php the_title(); ?>
                </a>

            </h2>


            <div class="kis-event-button-area">

                <a
                    href="<?php the_permalink(); ?>"
                    class="kis-event-button"
                >

                    <?php
                    echo
                        $type['is_archive']
                        ?
                        '視聴する'
                        :
                        '詳細を見る';
                    ?>

                    <span class="kis-event-arrow">
                        ›
                    </span>

                </a>

            </div>

        </div>

    </article>

    <?php
}


/* =========================================================
 * 9. イベント一覧
 * ========================================================= */

function kis_event_list_shortcode() {

    /*
     * 公開イベントを開催日順で取得
     *
     * 年別タブは「開催日」の年を基準にします。
     * 例：
     * 2026年に開催したセミナーを2027年にアーカイブ化しても、
     * 2026年タブに残ります。
     */
    $event_query =
        new WP_Query(
            array(
                'post_type'
                    =>
                'kis_event',

                'post_status'
                    =>
                'publish',

                'posts_per_page'
                    =>
                -1,

                'meta_key'
                    =>
                '_kis_event_date',

                'orderby'
                    =>
                'meta_value',

                'order'
                    =>
                'DESC',
            )
        );


    $events_by_year = array();


    if (
        $event_query
            ->have_posts()
    ) {

        while (
            $event_query
                ->have_posts()
        ) {

            $event_query
                ->the_post();


            $event_date =
                get_post_meta(
                    get_the_ID(),
                    '_kis_event_date',
                    true
                );


            /*
             * 開催日がないものは
             * 年判定できないため一覧タブには出さない
             */
            if ( ! $event_date ) {
                continue;
            }


            $timestamp =
                strtotime(
                    $event_date
                );


            if ( ! $timestamp ) {
                continue;
            }


            $year =
                wp_date(
                    'Y',
                    $timestamp
                );


            if (
                ! isset(
                    $events_by_year[
                        $year
                    ]
                )
            ) {

                $events_by_year[
                    $year
                ] = array();
            }


            $events_by_year[
                $year
            ][] =
                get_the_ID();
        }
    }


    wp_reset_postdata();


    /*
     * 年は 2026 → 2025 → 2024 の順。
     * 各年の中は
     * セミナー → アーカイブ配信 → 終了
     * の順に並べます。
     *
     * 同じ種別の中では開催日の新しいものを先に表示します。
     */
    if ( $events_by_year ) {

        krsort(
            $events_by_year,
            SORT_NUMERIC
        );


        $type_priority = array(
            'seminar' => 1,
            'archive' => 2,
            'ended'   => 3,
        );


        foreach (
            $events_by_year
            as $year => &$event_ids
        ) {

            usort(
                $event_ids,
                function ( $event_id_a, $event_id_b ) use ( $type_priority ) {

                    $type_a =
                        get_post_meta(
                            $event_id_a,
                            '_kis_event_type',
                            true
                        );

                    $type_b =
                        get_post_meta(
                            $event_id_b,
                            '_kis_event_type',
                            true
                        );


                    $priority_a =
                        isset( $type_priority[ $type_a ] )
                            ? $type_priority[ $type_a ]
                            : 99;

                    $priority_b =
                        isset( $type_priority[ $type_b ] )
                            ? $type_priority[ $type_b ]
                            : 99;


                    if ( $priority_a !== $priority_b ) {
                        return $priority_a <=> $priority_b;
                    }


                    $date_a =
                        get_post_meta(
                            $event_id_a,
                            '_kis_event_date',
                            true
                        );

                    $date_b =
                        get_post_meta(
                            $event_id_b,
                            '_kis_event_date',
                            true
                        );


                    return strcmp(
                        $date_b,
                        $date_a
                    );
                }
            );
        }

        unset( $event_ids );
    }


    ob_start();

    ?>

    <div class="kis-event-page">

        <div class="kis-event-header">

            <div class="kis-event-label">
                EVENT
            </div>

            <h1 class="kis-event-heading">
                イベント
            </h1>

            <p class="kis-event-description">
                セミナー・アーカイブ配信など、Forwarder-PROに関するイベント情報をご案内します。
            </p>

        </div>


        <?php if ( $events_by_year ) : ?>


            <?php

            $years =
                array_keys(
                    $events_by_year
                );

            $first_year =
                (string)
                reset(
                    $years
                );

            ?>


            <div
                class="kis-event-year-tabs"
                role="tablist"
                aria-label="開催年"
            >

                <?php foreach ( $years as $year ) : ?>

                    <button
                        type="button"
                        class="kis-event-year-tab<?php echo (string) $year === $first_year ? ' is-active' : ''; ?>"
                        data-kis-event-year="<?php echo esc_attr( $year ); ?>"
                        role="tab"
                        aria-selected="<?php echo (string) $year === $first_year ? 'true' : 'false'; ?>"
                    >
                        <?php
                        echo esc_html(
                            $year
                        );
                        ?>年
                    </button>

                <?php endforeach; ?>

            </div>


            <?php
            foreach (
                $events_by_year
                as
                $year => $post_ids
            ) :
            ?>

                <section
                    class="kis-event-year-panel<?php echo (string) $year === $first_year ? ' is-active' : ''; ?>"
                    data-kis-event-panel="<?php echo esc_attr( $year ); ?>"
                    role="tabpanel"
                >

                    <div class="kis-event-grid">

                        <?php

                        global $post;

                        foreach (
                            $post_ids
                            as
                            $event_post_id
                        ) {

                            $event_post =
                                get_post(
                                    $event_post_id
                                );


                            if ( ! $event_post ) {
                                continue;
                            }


                            /*
                             * get_the_ID() / the_title() / the_permalink() /
                             * アイキャッチ画像 / カスタム項目などが
                             * 各イベント投稿を正しく参照するように、
                             * WordPressのグローバル$postを切り替えます。
                             */
                            $post =
                                $event_post;

                            setup_postdata(
                                $post
                            );


                            kis_event_render_card();
                        }


                        wp_reset_postdata();

                        ?>

                    </div>

                </section>

            <?php endforeach; ?>


            <script>

            document.addEventListener(
                'DOMContentLoaded',
                function () {

                    const tabs =
                        document.querySelectorAll(
                            '.kis-event-year-tab'
                        );

                    const panels =
                        document.querySelectorAll(
                            '.kis-event-year-panel'
                        );


                    if (
                        ! tabs.length
                        ||
                        ! panels.length
                    ) {
                        return;
                    }


                    function activateYear(
                        selectedTab
                    ) {

                        const year =
                            selectedTab.getAttribute(
                                'data-kis-event-year'
                            );


                        tabs.forEach(
                            function ( tab ) {

                                tab.classList.remove(
                                    'is-active'
                                );

                                tab.setAttribute(
                                    'aria-selected',
                                    'false'
                                );
                            }
                        );


                        panels.forEach(
                            function ( panel ) {

                                panel.classList.remove(
                                    'is-active'
                                );
                            }
                        );


                        selectedTab.classList.add(
                            'is-active'
                        );

                        selectedTab.setAttribute(
                            'aria-selected',
                            'true'
                        );


                        const target =
                            document.querySelector(
                                '.kis-event-year-panel[data-kis-event-panel="'
                                +
                                year
                                +
                                '"]'
                            );


                        if ( target ) {

                            target.classList.add(
                                'is-active'
                            );
                        }
                    }


                    tabs.forEach(
                        function ( tab ) {

                            tab.addEventListener(
                                'click',
                                function () {

                                    activateYear(
                                        tab
                                    );
                                }
                            );
                        }
                    );
                }
            );

            </script>


        <?php else : ?>


            <p class="kis-event-empty">
                現在掲載中のイベントはありません。
            </p>


        <?php endif; ?>

    </div>

    <?php

    return ob_get_clean();
}
add_shortcode(
    'kis_event_list',
    'kis_event_list_shortcode'
);


/* =========================================================
 * 10. 個別ページ内容
 * ========================================================= */

function kis_event_add_detail_to_content( $content ) {

    if (
        ! is_singular(
            'kis_event'
        )
        ||
        ! in_the_loop()
        ||
        ! is_main_query()
    ) {
        return $content;
    }


    $post_id = get_the_ID();

    $event_date = get_post_meta(
        $post_id,
        '_kis_event_date',
        true
    );

    $event_time = get_post_meta(
        $post_id,
        '_kis_event_time',
        true
    );

    $event_format = get_post_meta(
        $post_id,
        '_kis_event_format',
        true
    );

    $event_fee = get_post_meta(
        $post_id,
        '_kis_event_fee',
        true
    );

    $seminar_url = get_post_meta(
        $post_id,
        '_kis_event_seminar_url',
        true
    );

    if ( ! $seminar_url ) {

        $seminar_url = get_post_meta(
            $post_id,
            '_kis_event_url',
            true
        );
    }

    $event_button = get_post_meta(
        $post_id,
        '_kis_event_button',
        true
    );

    $type = kis_event_get_type_info(
        $post_id
    );


    /*
     * ボタン設定
     */

    if (
        $type['is_archive']
    ) {

        $button_text =
            $event_button
            ?
            $event_button
            :
            'アーカイブ視聴を申し込む';


        /*
         * アーカイブ申込固定ページへ
         * event_id を付けて渡す
         */

        $button_url =
            add_query_arg(
                'event_id',
                $post_id,
                home_url(
                    '/アーカイブ視聴申込/'
                )
            );

    } elseif (
        $type['is_ended']
    ) {

        /*
         * 終了イベントは詳細ページを残すが、
         * 申込ボタンは表示しない
         */

        $button_text = '';
        $button_url  = '';

    } else {

        $button_text =
            $event_button
            ?
            $event_button
            :
            'セミナーに申し込む';

        $button_url =
            $seminar_url;
    }


    ob_start();

    ?>

    <div class="kis-event-detail">


        <div
            class="kis-event-detail-tag<?php echo $type['is_archive'] ? ' archive' : ( $type['is_ended'] ? ' ended' : '' ); ?>"
        >
            <?php
            echo esc_html(
                $type['name']
            );
            ?>
        </div>


        <?php
        if (
            has_post_thumbnail(
                $post_id
            )
        ) :
        ?>

            <div class="kis-event-detail-image">

                <?php

                echo get_the_post_thumbnail(
                    $post_id,
                    'large',
                    array(
                        'alt'
                            =>
                        get_the_title(
                            $post_id
                        ),
                    )
                );

                ?>

            </div>

        <?php
        endif;
        ?>


        <?php
        if (
            ! $type['is_archive']
        ) :
        ?>

            <div class="kis-event-detail-info">

                <?php
                if (
                    $event_date
                    ||
                    $event_time
                ) :
                ?>

                    <div class="kis-event-detail-row">

                        <div class="kis-event-detail-label">
                            開催日時
                        </div>

                        <div class="kis-event-detail-value">

                            <?php

                            echo esc_html(
                                kis_event_format_date(
                                    $event_date
                                )
                            );

                            if ( $event_time ) {

                                echo ' ';

                                echo esc_html(
                                    $event_time
                                );
                            }

                            ?>

                        </div>

                    </div>

                <?php
                endif;
                ?>


                <?php
                if ( $event_format ) :
                ?>

                    <div class="kis-event-detail-row">

                        <div class="kis-event-detail-label">
                            開催形式
                        </div>

                        <div class="kis-event-detail-value">
                            <?php
                            echo esc_html(
                                $event_format
                            );
                            ?>
                        </div>

                    </div>

                <?php
                endif;
                ?>


                <?php
                if ( $event_fee ) :
                ?>

                    <div class="kis-event-detail-row">

                        <div class="kis-event-detail-label">
                            参加費
                        </div>

                        <div class="kis-event-detail-value">
                            <?php
                            echo esc_html(
                                $event_fee
                            );
                            ?>
                        </div>

                    </div>

                <?php
                endif;
                ?>

            </div>

        <?php
        endif;
        ?>


        <?php
        if ( $content ) :
        ?>

            <div class="kis-event-detail-content">
                <?php echo $content; ?>
            </div>

        <?php
        endif;
        ?>


        <?php
        if ( $button_url ) :
        ?>

            <div class="kis-event-detail-cta">

                <a
                    href="<?php echo esc_url( $button_url ); ?>"
                    class="kis-event-detail-button"
                    <?php
                    if (
                        ! $type['is_archive']
                    ) :
                    ?>
                        target="_blank"
                        rel="noopener noreferrer"
                    <?php endif; ?>
                >

                    <?php
                    echo esc_html(
                        $button_text
                    );
                    ?>

                    <span>
                        →
                    </span>

                </a>

            </div>

        <?php
        endif;
        ?>


        <div class="kis-event-detail-back">

            <a
                href="<?php echo esc_url( home_url( '/イベント/' ) ); ?>"
            >
                ← イベント一覧へ戻る
            </a>

        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_filter(
    'the_content',
    'kis_event_add_detail_to_content'
);


/* =========================================================
 * 11. イベント専用テンプレート
 * ========================================================= */

function kis_event_custom_single_template( $template ) {

    if (
        is_singular(
            'kis_event'
        )
    ) {

        $plugin_template =
            plugin_dir_path(
                __FILE__
            )
            .
            'templates/single-kis_event.php';


        if (
            file_exists(
                $plugin_template
            )
        ) {

            return $plugin_template;
        }
    }


    return $template;
}

add_filter(
    'template_include',
    'kis_event_custom_single_template'
);


/* =========================================================
 * 12. 管理画面：イベント種別切替
 * ========================================================= */

function kis_event_admin_type_switcher() {

    $screen = get_current_screen();

    if (
        ! $screen
        ||
        'kis_event'
        !==
        $screen->post_type
    ) {
        return;
    }

    ?>

    <script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const typeSelect =
                document.getElementById(
                    'kis_event_type'
                );

            const seminarFields =
                document.getElementById(
                    'kis_seminar_fields'
                );

            const archiveFields =
                document.getElementById(
                    'kis_archive_fields'
                );

            const archiveMessage =
                document.getElementById(
                    'kis_archive_message'
                );

            const buttonField =
                document.getElementById(
                    'kis_event_button'
                );

            const buttonHelp =
                document.getElementById(
                    'kis_button_help'
                );


            if (
                ! typeSelect
                ||
                ! seminarFields
                ||
                ! archiveFields
            ) {
                return;
            }


            function updateFields() {

                const isArchive =
                    typeSelect.value
                    ===
                    'archive';

                const isEnded =
                    typeSelect.value
                    ===
                    'ended';


                if ( isArchive ) {

                    seminarFields.style.display =
                        'none';

                    archiveFields.style.display =
                        'block';


                    if ( archiveMessage ) {

                        archiveMessage.style.display =
                            'block';
                    }


                    if ( buttonField ) {

                        buttonField.placeholder =
                            '例：アーカイブ視聴を申し込む';
                    }


                    if ( buttonHelp ) {

                        buttonHelp.textContent =
                            '未入力の場合は「アーカイブ視聴を申し込む」と表示します。';
                    }

                } else if ( isEnded ) {

                    seminarFields.style.display =
                        'none';

                    archiveFields.style.display =
                        'none';

                    if ( archiveMessage ) {
                        archiveMessage.style.display = 'none';
                    }

                    if ( buttonField ) {
                        buttonField.closest('p').style.display = 'none';
                    }

                } else {

                    seminarFields.style.display =
                        'block';

                    if ( buttonField ) {
                        buttonField.closest('p').style.display = 'block';
                    }

                    archiveFields.style.display =
                        'none';


                    if ( archiveMessage ) {

                        archiveMessage.style.display =
                            'none';
                    }


                    if ( buttonField ) {

                        buttonField.placeholder =
                            '例：セミナーに申し込む';
                    }


                    if ( buttonHelp ) {

                        buttonHelp.textContent =
                            '未入力の場合は「セミナーに申し込む」と表示します。';
                    }
                }
            }


            typeSelect.addEventListener(
                'change',
                updateFields
            );


            updateFields();

        }
    );

    </script>

    <?php
}

add_action(
    'admin_footer-post.php',
    'kis_event_admin_type_switcher'
);

add_action(
    'admin_footer-post-new.php',
    'kis_event_admin_type_switcher'
);


/* =========================================================
 * 13. 終了済みセミナー警告
 * ========================================================= */

function kis_event_expired_notice_callback( $post ) {

    ?>

    <div
        style="
            padding:5px 2px;
            line-height:1.7;
        "
    >

        <strong style="color:#996800;">
            このセミナーは終了しています。
        </strong>

        <p>
            アーカイブ動画の準備が完了したら、
            「イベント種別」を
            <strong>アーカイブ配信</strong>
            に変更し、
            YouTube限定公開URLを登録してください。
        </p>

        <p style="margin-bottom:0;color:#666;font-size:12px;">
            ※自動的にアーカイブ配信へ変更されることはありません。
        </p>

    </div>

    <?php
}


function kis_event_add_expired_notice_meta_box() {

    global $post;

    if (
        ! $post
        ||
        'kis_event' !== $post->post_type
    ) {
        return;
    }


    $event_type = get_post_meta(
        $post->ID,
        '_kis_event_type',
        true
    );

    $event_date = get_post_meta(
        $post->ID,
        '_kis_event_date',
        true
    );


    if (
        'seminar' !== $event_type
        ||
        ! $event_date
    ) {
        return;
    }


    $today = current_time(
        'Y-m-d'
    );


    if (
        $event_date >= $today
    ) {
        return;
    }


    add_meta_box(
        'kis_event_expired_notice',
        '⚠ アーカイブ化を確認してください',
        'kis_event_expired_notice_callback',
        'kis_event',
        'normal',
        'high'
    );
}

add_action(
    'add_meta_boxes_kis_event',
    'kis_event_add_expired_notice_meta_box'
);


/* =========================================================
 * 14. 有効化・無効化
 * ========================================================= */

function kis_event_activate() {

    kis_register_event_post_type();

    flush_rewrite_rules();
}

register_activation_hook(
    __FILE__,
    'kis_event_activate'
);


function kis_event_deactivate() {

    flush_rewrite_rules();
}

register_deactivation_hook(
    __FILE__,
    'kis_event_deactivate'
);
/* =========================================================
 * 15. MW WP Form：アーカイブ視聴申込連携
 * フォーム識別子：34
 * ========================================================= */


/*
 * URLまたはフォーム送信データから
 * 正しいアーカイブイベントを取得
 */
function kis_get_archive_request_event() {

    $event_id = 0;


    /*
     * 最初に申込ページを開いたとき
     * ?event_id=123
     */
    if (
        isset( $_GET['event_id'] )
        &&
        ! is_array( $_GET['event_id'] )
    ) {

        $event_id =
            absint(
                wp_unslash(
                    $_GET['event_id']
                )
            );
    }


    /*
     * 確認画面・送信時
     */
    if (
        ! $event_id
        &&
        isset( $_POST['event_id'] )
        &&
        ! is_array( $_POST['event_id'] )
    ) {

        $event_id =
            absint(
                wp_unslash(
                    $_POST['event_id']
                )
            );
    }


    if ( ! $event_id ) {
        return false;
    }


    /*
     * イベント投稿か確認
     */
    if (
        'kis_event'
        !==
        get_post_type(
            $event_id
        )
    ) {
        return false;
    }


    /*
     * 公開済みか確認
     */
    if (
        'publish'
        !==
        get_post_status(
            $event_id
        )
    ) {
        return false;
    }


    /*
     * アーカイブ配信か確認
     */
    $event_type =
        get_post_meta(
            $event_id,
            '_kis_event_type',
            true
        );


    if (
        'archive'
        !==
        $event_type
    ) {
        return false;
    }


    return $event_id;
}


/*
 * MW WP Formのhidden項目へ
 * event_id / event_title を自動設定
 */
function kis_archive_mwform_value(
    $value,
    $name
) {

    /*
     * 確認画面などですでに値がある場合は
     * その値を優先
     */
    if (
        '' !== $value
        &&
        null !== $value
    ) {
        return $value;
    }


    $event_id =
        kis_get_archive_request_event();


    if ( ! $event_id ) {
        return $value;
    }


    if (
        'event_id'
        ===
        $name
    ) {

        return (string) $event_id;
    }


    if (
        'event_title'
        ===
        $name
    ) {

        return get_the_title(
            $event_id
        );
    }


    return $value;
}

add_filter(
    'mwform_value_mw-wp-form-34',
    'kis_archive_mwform_value',
    10,
    2
);


/*
 * フォーム上に
 * 「視聴希望アーカイブ」を表示するショートコード
 */
function kis_archive_request_title_shortcode() {

    $event_id =
        kis_get_archive_request_event();


    if ( ! $event_id ) {

        return
            '<div class="kis-archive-request-error">'
            .
            '<strong>視聴するアーカイブを確認できませんでした。</strong>'
            .
            '<br>'
            .
            'イベントページから、もう一度アーカイブを選択してください。'
            .
            '</div>';
    }


    $title =
        get_the_title(
            $event_id
        );


    ob_start();

    ?>

    <div class="kis-archive-request-target">

        <div class="kis-archive-request-target-label">
            視聴希望アーカイブ
        </div>

        <div class="kis-archive-request-target-title">
            <?php
            echo esc_html(
                $title
            );
            ?>
        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode(
    'kis_archive_request_title',
    'kis_archive_request_title_shortcode'
);
/* =========================================================
 * 16. MW WP Form：YouTube限定公開URL用
 * カスタムメールタグ
 *
 * メール本文の
 * {kis_youtube_url}
 * を該当アーカイブのURLへ変換
 * ========================================================= */

function kis_archive_youtube_custom_mail_tag(
    $value,
    $key,
    $insert_contact_data_id
) {

    /*
     * 今回処理するのは
     * {kis_youtube_url}
     * だけ
     */
    if (
        'kis_youtube_url'
        !==
        $key
    ) {
        return $value;
    }


    /*
     * MW WP Formから送信された
     * event_idを取得
     */
    $event_id = 0;

    if (
        isset( $_POST['event_id'] )
        &&
        ! is_array( $_POST['event_id'] )
    ) {

        $event_id =
            absint(
                wp_unslash(
                    $_POST['event_id']
                )
            );
    }


    /*
     * event_idが取れない場合
     */
    if ( ! $event_id ) {

        return
            '視聴URLを取得できませんでした。'
            .
            '恐れ入りますが弊社までお問い合わせください。';
    }


    /*
     * kis_event投稿か確認
     */
    if (
        'kis_event'
        !==
        get_post_type(
            $event_id
        )
    ) {

        return
            '視聴URLを取得できませんでした。'
            .
            '恐れ入りますが弊社までお問い合わせください。';
    }


    /*
     * 公開済みか確認
     */
    if (
        'publish'
        !==
        get_post_status(
            $event_id
        )
    ) {

        return
            '視聴URLを取得できませんでした。'
            .
            '恐れ入りますが弊社までお問い合わせください。';
    }


    /*
     * アーカイブ配信か確認
     */
    $event_type =
        get_post_meta(
            $event_id,
            '_kis_event_type',
            true
        );


    if (
        'archive'
        !==
        $event_type
    ) {

        return
            '視聴URLを取得できませんでした。'
            .
            '恐れ入りますが弊社までお問い合わせください。';
    }


    /*
     * YouTube限定公開URL取得
     */
    $youtube_url =
        get_post_meta(
            $event_id,
            '_kis_event_youtube_url',
            true
        );


    /*
     * URL未登録
     */
    if ( ! $youtube_url ) {

        return
            '現在、視聴URLを準備中です。'
            .
            '準備が整い次第ご案内いたします。';
    }


    /*
     * URLとして不正な値を除外
     */
    $youtube_url =
        esc_url_raw(
            $youtube_url
        );


    if ( ! $youtube_url ) {

        return
            '視聴URLを取得できませんでした。'
            .
            '恐れ入りますが弊社までお問い合わせください。';
    }


    return $youtube_url;
}


add_filter(
    'mwform_custom_mail_tag_mw-wp-form-34',
    'kis_archive_youtube_custom_mail_tag',
    10,
    3
);
/* =========================================================
 * 17. イベント管理一覧を見やすくする
 * ========================================================= */


/*
 * 管理画面のイベント一覧カラム
 */
function kis_event_admin_columns( $columns ) {

    $new_columns = array();

    foreach ( $columns as $key => $label ) {

        /*
         * チェックボックス
         */
        if ( 'cb' === $key ) {
            $new_columns[$key] = $label;
            continue;
        }


        /*
         * タイトルの後ろに独自カラムを追加
         */
        if ( 'title' === $key ) {

            $new_columns[$key] = $label;

            $new_columns['kis_event_type'] =
                '種別';

            $new_columns['kis_event_date'] =
                '開催日';

            $new_columns['kis_event_status'] =
                '状態';

            $new_columns['kis_event_archived_at'] =
                'アーカイブ切替日';

            continue;
        }


        /*
         * WordPress標準の日付カラムは残す
         */
        $new_columns[$key] = $label;
    }


    return $new_columns;
}

add_filter(
    'manage_kis_event_posts_columns',
    'kis_event_admin_columns'
);


/*
 * 各カラムの内容
 */
function kis_event_admin_column_content(
    $column,
    $post_id
) {

    $event_type =
        get_post_meta(
            $post_id,
            '_kis_event_type',
            true
        );


    $event_date =
        get_post_meta(
            $post_id,
            '_kis_event_date',
            true
        );


    $archived_at =
        get_post_meta(
            $post_id,
            '_kis_event_archived_at',
            true
        );


    /*
     * 種別
     */
    if (
        'kis_event_type'
        ===
        $column
    ) {

        if (
            'archive'
            ===
            $event_type
        ) {

            echo
                '<span class="kis-admin-type kis-admin-type-archive">'
                .
                'アーカイブ配信'
                .
                '</span>';

        } elseif (
            'ended'
            ===
            $event_type
        ) {

            echo
                '<span class="kis-admin-type kis-admin-type-ended">'
                .
                '終了'
                .
                '</span>';

        } else {

            echo
                '<span class="kis-admin-type kis-admin-type-seminar">'
                .
                'セミナー'
                .
                '</span>';
        }
    }


    /*
     * 開催日
     */
    if (
        'kis_event_date'
        ===
        $column
    ) {

        if (
            $event_date
        ) {

            echo esc_html(
                kis_event_format_date(
                    $event_date
                )
            );

        } else {

            echo '―';
        }
    }


    /*
     * 状態
     */
    if (
        'kis_event_status'
        ===
        $column
    ) {

        /*
         * 終了
         */
        if (
            'ended'
            ===
            $event_type
        ) {

            echo
                '<span class="kis-admin-status kis-admin-status-ended-fixed">'
                .
                '終了'
                .
                '</span>';

            return;
        }


        /*
         * アーカイブ
         */
        if (
            'archive'
            ===
            $event_type
        ) {

            echo
                '<span class="kis-admin-status kis-admin-status-archive">'
                .
                'アーカイブ公開中'
                .
                '</span>';

            return;
        }


        /*
         * セミナー
         */
        if (
            $event_date
        ) {

            $today =
                current_time(
                    'Y-m-d'
                );


            if (
                $event_date
                <
                $today
            ) {

                echo
                    '<span class="kis-admin-status kis-admin-status-ended">'
                    .
                    '⚠ 終了済み'
                    .
                    '</span>';

            } elseif (
                $event_date
                ===
                $today
            ) {

                echo
                    '<span class="kis-admin-status kis-admin-status-today">'
                    .
                    '本日開催'
                    .
                    '</span>';

            } else {

                echo
                    '<span class="kis-admin-status kis-admin-status-upcoming">'
                    .
                    '開催予定'
                    .
                    '</span>';
            }

        } else {

            echo
                '<span class="kis-admin-status">'
                .
                '日付未設定'
                .
                '</span>';
        }
    }


    /*
     * アーカイブ切替日
     */
    if (
        'kis_event_archived_at'
        ===
        $column
    ) {

        if (
            'archive'
            ===
            $event_type
            &&
            $archived_at
        ) {

            $timestamp =
                strtotime(
                    $archived_at
                );


            if (
                $timestamp
            ) {

                echo esc_html(
                    wp_date(
                        'Y.m.d',
                        $timestamp
                    )
                );

            } else {

                echo '―';
            }

        } else {

            echo '―';
        }
    }
}

add_action(
    'manage_kis_event_posts_custom_column',
    'kis_event_admin_column_content',
    10,
    2
);


/* =========================================================
 * 管理一覧用CSS
 * ========================================================= */

function kis_event_admin_list_css() {

    $screen =
        get_current_screen();


    if (
        ! $screen
        ||
        'edit-kis_event'
        !==
        $screen->id
    ) {
        return;
    }

    ?>

    <style>

        /* 種別 */

        .kis-admin-type {
            display: inline-block;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .kis-admin-type-seminar {
            background: #e8f1ff;
            color: #174a91;
        }

        .kis-admin-type-archive {
            background: #333;
            color: #fff;
        }

        .kis-admin-type-ended {
            background: #e5e5e5;
            color: #555;
        }


        /* 状態 */

        .kis-admin-status {
            font-weight: 700;
        }

        .kis-admin-status-upcoming {
            color: #174a91;
        }

        .kis-admin-status-today {
            color: #008a20;
        }

        .kis-admin-status-ended {
            color: #b32d2e;
        }

        .kis-admin-status-ended-fixed {
            color: #666;
        }

        .kis-admin-status-archive {
            color: #555;
        }


        /* カラム幅 */

        .column-kis_event_type {
            width: 130px;
        }

        .column-kis_event_date {
            width: 150px;
        }

        .column-kis_event_status {
            width: 150px;
        }

        .column-kis_event_archived_at {
            width: 150px;
        }

    </style>

    <?php
}

add_action(
    'admin_head',
    'kis_event_admin_list_css'
);