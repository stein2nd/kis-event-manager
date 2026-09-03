<?php
/**
 * KIS イベント個別ページテンプレート
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id = get_the_ID();

$type = kis_event_get_type_info( $post_id );

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

/*
 * 旧データとの互換性
 */
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


/* =========================================================
 * ボタン設定
 * ========================================================= */

if ( $type['is_archive'] ) {

    /*
     * アーカイブの場合
     *
     * YouTubeへ直接リンクしない。
     * 視聴申込ページへ event_id を渡す。
     */

    $button_text =
        $event_button
        ?
        $event_button
        :
        'アーカイブ視聴を申し込む';


    $button_url =
        add_query_arg(
            'event_id',
            $post_id,
            home_url(
                '/アーカイブ視聴申込/'
            )
        );

} else {

    /*
     * セミナーの場合
     */

    $button_text =
        $event_button
        ?
        $event_button
        :
        'セミナーに申し込む';


    $button_url =
        $seminar_url;
}


/* =========================================================
 * 本文取得
 * ========================================================= */

$raw_content =
    get_post_field(
        'post_content',
        $post_id
    );


/*
 * the_content フィルターによる
 * イベント詳細の二重表示を防止
 */

remove_filter(
    'the_content',
    'kis_event_add_detail_to_content'
);


$event_content =
    apply_filters(
        'the_content',
        $raw_content
    );


add_filter(
    'the_content',
    'kis_event_add_detail_to_content'
);

?>
<!doctype html>

<html <?php language_attributes(); ?>>

<head>

    <meta charset="<?php bloginfo( 'charset' ); ?>">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <?php wp_head(); ?>

</head>


<body <?php body_class(); ?>>

<?php wp_body_open(); ?>


<?php

/*
 * ブロックテーマのヘッダー
 */

echo do_blocks(
    '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
);

?>


<main class="kis-single-event-page">

    <div class="kis-single-event-inner">


        <div class="kis-single-event-label">
            EVENT
        </div>


        <h1 class="kis-single-event-title">

            <?php
            echo esc_html(
                get_the_title(
                    $post_id
                )
            );
            ?>

        </h1>


        <!-- イベント種別 -->

        <div
            class="kis-event-detail-tag<?php echo $type['is_archive'] ? ' archive' : ''; ?>"
        >

            <?php
            echo esc_html(
                $type['name']
            );
            ?>

        </div>


        <!-- アイキャッチ -->

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


        <!-- セミナー開催情報 -->

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


                            if (
                                $event_time
                            ) {

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
                if (
                    $event_format
                ) :
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
                if (
                    $event_fee
                ) :
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


        <!-- 本文 -->

        <?php
        if (
            $event_content
        ) :
        ?>

            <div class="kis-event-detail-content">

                <?php
                echo $event_content;
                ?>

            </div>

        <?php
        endif;
        ?>


        <!-- CTA -->

        <?php
        if (
            $button_url
        ) :
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

                    <?php
                    endif;
                    ?>
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


        <!-- 一覧へ戻る -->

        <div class="kis-event-detail-back">

            <a
                href="<?php echo esc_url( home_url( '/イベント/' ) ); ?>"
            >
                ← イベント一覧へ戻る
            </a>

        </div>


    </div>

</main>


<?php

/*
 * ブロックテーマのフッター
 */

echo do_blocks(
    '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->'
);

?>


<?php wp_footer(); ?>

</body>

</html>