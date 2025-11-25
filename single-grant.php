<?php
/**
 * Grant Single Page - Ultimate Perfect Edition v201
 * 補助金詳細ページ - 究極完全版（修正版）
 * 
 * 【修正内容】
 * - CTAの文字色を白に修正
 * - PC版AIアシスタント機能を追加
 * - 下部バナー削除（共通バナー使用）
 * 
 * @package Grant_Insight_Ultimate
 * @version 201.0.0
 */

if (!defined('ABSPATH')) exit;

if (!have_posts()) {
    wp_redirect(home_url('/404'), 302);
    exit;
}

get_header();
the_post();

// ===================================
// データ取得・整形
// ===================================
$post_id = get_the_ID();
$canonical_url = get_permalink($post_id);
$site_name = get_bloginfo('name');

// ACFデータ完全取得
$grant = array(
    'organization' => get_field('organization', $post_id) ?: '',
    'max_amount' => get_field('max_amount', $post_id) ?: '',
    'max_amount_numeric' => intval(get_field('max_amount_numeric', $post_id)),
    'min_amount_numeric' => intval(get_field('min_amount_numeric', $post_id)),
    'subsidy_rate' => get_field('subsidy_rate', $post_id) ?: '',
    'subsidy_rate_detailed' => get_field('subsidy_rate_detailed', $post_id) ?: '',
    'deadline' => get_field('deadline', $post_id) ?: '',
    'deadline_date' => get_field('deadline_date', $post_id) ?: '',
    'start_date' => get_field('start_date', $post_id) ?: '',
    'application_period' => get_field('application_period', $post_id) ?: '',
    'grant_target' => get_field('grant_target', $post_id) ?: '',
    'area_notes' => get_field('area_notes', $post_id) ?: '',
    'contact_info' => get_field('contact_info', $post_id) ?: '',
    'contact_phone' => get_field('contact_phone', $post_id) ?: '',
    'contact_email' => get_field('contact_email', $post_id) ?: '',
    'official_url' => get_field('official_url', $post_id) ?: '',
    'application_status' => get_field('application_status', $post_id) ?: 'open',
    'required_documents' => get_field('required_documents', $post_id) ?: '',
    'required_documents_detailed' => get_field('required_documents_detailed', $post_id) ?: '',
    'adoption_rate' => floatval(get_field('adoption_rate', $post_id)),
    'adoption_count' => intval(get_field('adoption_count', $post_id)),
    'application_count' => intval(get_field('application_count', $post_id)),
    'grant_difficulty' => get_field('grant_difficulty', $post_id) ?: 'normal',
    'difficulty_level' => get_field('difficulty_level', $post_id) ?: '中級',
    'is_featured' => get_field('is_featured', $post_id) ?: false,
    'views_count' => intval(get_field('views_count', $post_id)),
    'bookmark_count' => intval(get_field('bookmark_count', $post_id)),
    'ai_summary' => get_field('ai_summary', $post_id) ?: '',
    'eligible_expenses' => get_field('eligible_expenses', $post_id) ?: '',
    'eligible_expenses_detailed' => get_field('eligible_expenses_detailed', $post_id) ?: '',
    'ineligible_expenses' => get_field('ineligible_expenses', $post_id) ?: '',
    'application_method' => get_field('application_method', $post_id) ?: '',
    'application_flow' => get_field('application_flow', $post_id) ?: '',
    'regional_limitation' => get_field('regional_limitation', $post_id) ?: '',
    'success_cases' => get_field('success_cases', $post_id) ?: array(),
    'supervisor_name' => get_field('supervisor_name', $post_id) ?: '',
    'supervisor_title' => get_field('supervisor_title', $post_id) ?: '',
    'supervisor_profile' => get_field('supervisor_profile', $post_id) ?: '',
    'supervisor_image' => get_field('supervisor_image', $post_id) ?: '',
    'source_url' => get_field('source_url', $post_id) ?: '',
    'source_name' => get_field('source_name', $post_id) ?: '',
    'last_verified_date' => get_field('last_verified_date', $post_id) ?: '',
    'similar_grants' => get_field('similar_grants', $post_id) ?: array(),
    'application_tips' => get_field('application_tips', $post_id) ?: '',
    'common_mistakes' => get_field('common_mistakes', $post_id) ?: '',
    'preparation_time' => get_field('preparation_time', $post_id) ?: '',
    'review_period' => get_field('review_period', $post_id) ?: '',
);

// デフォルト監修者情報
if (empty($grant['supervisor_name'])) {
    $grant['supervisor_name'] = '補助金インサイト編集部';
    $grant['supervisor_title'] = '中小企業診断士監修';
    $grant['supervisor_profile'] = '補助金・助成金の専門家チーム。年間500件以上の補助金情報を調査・検証し、正確でわかりやすい情報提供を行っています。';
}

// 地域制限判定
$is_nationwide = ($grant['regional_limitation'] === 'nationwide');

// タクソノミー
$taxonomies = array(
    'categories' => wp_get_post_terms($post_id, 'grant_category') ?: array(),
    'prefectures' => wp_get_post_terms($post_id, 'grant_prefecture') ?: array(),
    'municipalities' => wp_get_post_terms($post_id, 'grant_municipality') ?: array(),
    'industries' => wp_get_post_terms($post_id, 'grant_industry') ?: array(),
    'tags' => wp_get_post_tags($post_id) ?: array(),
);

// 地域表示ロジック
function gi_format_region_display($terms, $limit = 5, $suffix = '') {
    if (empty($terms) || is_wp_error($terms)) {
        return array('html' => '', 'has_more' => false, 'more_count' => 0, 'all' => array());
    }
    
    $total = count($terms);
    $display_terms = array_slice($terms, 0, $limit);
    $has_more = $total > $limit;
    $more_count = $total - $limit;
    
    $html = '';
    foreach ($display_terms as $index => $term) {
        $html .= '<a href="' . esc_url(get_term_link($term)) . '" class="gi-tag">' . esc_html($term->name) . '</a>';
    }
    
    if ($has_more) {
        $html .= '<span class="gi-tag-more">他' . $more_count . $suffix . '</span>';
    }
    
    return array(
        'html' => $html,
        'has_more' => $has_more,
        'more_count' => $more_count,
        'all' => $terms
    );
}

$prefecture_display = gi_format_region_display($taxonomies['prefectures'], 5, '都道府県');
$municipality_display = gi_format_region_display($taxonomies['municipalities'], 5, '市町村');
$industry_display = gi_format_region_display($taxonomies['industries'], 5, '業種');

// パンくずリスト
$breadcrumbs = array(
    array('name' => 'ホーム', 'url' => home_url('/')),
    array('name' => '補助金一覧', 'url' => home_url('/grants/')),
);

if (!empty($taxonomies['categories'])) {
    $main_cat = $taxonomies['categories'][0];
    $breadcrumbs[] = array(
        'name' => $main_cat->name,
        'url' => get_term_link($main_cat)
    );
}

$breadcrumbs[] = array(
    'name' => get_the_title(),
    'url' => $canonical_url
);

// 金額フォーマット
function gi_format_amount($amount) {
    if ($amount <= 0) return '';
    if ($amount >= 100000000) {
        return number_format($amount / 100000000, 1) . '億円';
    } elseif ($amount >= 10000) {
        return number_format($amount / 10000) . '万円';
    } else {
        return number_format($amount) . '円';
    }
}

$formatted_max_amount = gi_format_amount($grant['max_amount_numeric']);
$formatted_min_amount = gi_format_amount($grant['min_amount_numeric']);

if (!$formatted_max_amount && $grant['max_amount']) {
    $formatted_max_amount = $grant['max_amount'];
}

// 金額表示テキスト
$amount_display = '';
if ($formatted_min_amount && $formatted_max_amount) {
    $amount_display = $formatted_min_amount . ' 〜 ' . $formatted_max_amount;
} elseif ($formatted_max_amount) {
    $amount_display = '最大 ' . $formatted_max_amount;
}

// 補助率表示
$subsidy_rate_display = $grant['subsidy_rate_detailed'] ?: $grant['subsidy_rate'];

// 締切情報
$deadline_info = '';
$deadline_class = '';
$days_remaining = 0;
$deadline_urgency = 'normal';

if ($grant['deadline_date']) {
    $deadline_timestamp = strtotime($grant['deadline_date']);
    if ($deadline_timestamp) {
        $deadline_info = date('Y年n月j日', $deadline_timestamp);
        $current_time = current_time('timestamp');
        $days_remaining = ceil(($deadline_timestamp - $current_time) / 86400);
        
        if ($days_remaining <= 0) {
            $deadline_class = 'closed';
            $deadline_urgency = 'closed';
        } elseif ($days_remaining <= 3) {
            $deadline_class = 'critical';
            $deadline_urgency = 'critical';
        } elseif ($days_remaining <= 7) {
            $deadline_class = 'urgent';
            $deadline_urgency = 'urgent';
        } elseif ($days_remaining <= 14) {
            $deadline_class = 'warning';
            $deadline_urgency = 'warning';
        } elseif ($days_remaining <= 30) {
            $deadline_class = 'soon';
            $deadline_urgency = 'soon';
        }
    }
} elseif ($grant['deadline']) {
    $deadline_info = $grant['deadline'];
}

// 難易度
$difficulty_map = array(
    'very_easy' => array('label' => 'とても易しい', 'level' => 1, 'stars' => 1, 'desc' => '初めての方でも安心', 'color' => '#10B981'),
    'easy' => array('label' => '易しい', 'level' => 2, 'stars' => 2, 'desc' => '基本的な書類で申請可能', 'color' => '#34D399'),
    'normal' => array('label' => '普通', 'level' => 3, 'stars' => 3, 'desc' => '一般的な難易度', 'color' => '#FBBF24'),
    'hard' => array('label' => '難しい', 'level' => 4, 'stars' => 4, 'desc' => '専門知識が必要', 'color' => '#F97316'),
    'expert' => array('label' => '専門家向け', 'level' => 5, 'stars' => 5, 'desc' => '専門家への相談推奨', 'color' => '#EF4444'),
);
$difficulty = $difficulty_map[$grant['grant_difficulty']] ?? $difficulty_map['normal'];

// ステータス
$status_map = array(
    'open' => array('label' => '募集中', 'class' => 'open', 'icon' => '●'),
    'closed' => array('label' => '募集終了', 'class' => 'closed', 'icon' => '×'),
    'upcoming' => array('label' => '募集予定', 'class' => 'upcoming', 'icon' => '○'),
    'suspended' => array('label' => '一時停止', 'class' => 'suspended', 'icon' => '△'),
);
$status = $status_map[$grant['application_status']] ?? $status_map['open'];

// 閲覧数更新
if (!is_user_logged_in()) {
    update_post_meta($post_id, 'views_count', $grant['views_count'] + 1);
    $grant['views_count']++;
}

// 読了時間
$content = get_the_content();
$word_count = mb_strlen(strip_tags($content), 'UTF-8');
$reading_time = max(1, ceil($word_count / 400));

// OGP画像
$og_image = get_the_post_thumbnail_url($post_id, 'large');
if (!$og_image) {
    $og_image = get_template_directory_uri() . '/assets/images/og-grant-default.jpg';
}
if (!$og_image) {
    $og_image = get_site_icon_url(512);
}

// メタディスクリプション
$meta_desc = '';
if ($grant['ai_summary']) {
    $meta_desc = mb_substr(wp_strip_all_tags($grant['ai_summary']), 0, 120, 'UTF-8');
} elseif (has_excerpt()) {
    $meta_desc = mb_substr(wp_strip_all_tags(get_the_excerpt()), 0, 120, 'UTF-8');
} else {
    $meta_desc = mb_substr(wp_strip_all_tags($content), 0, 120, 'UTF-8');
}

// SEO用タイトル
$seo_title = get_the_title();
if ($amount_display) {
    $seo_title .= '（' . $amount_display . '）';
}

// 最終確認日
$last_verified = $grant['last_verified_date'] ?: get_the_modified_date('Y-m-d');
$last_verified_display = date('Y年n月j日', strtotime($last_verified));

// 情報ソース
$source_name = $grant['source_name'] ?: $grant['organization'];
$source_url = $grant['source_url'] ?: $grant['official_url'];

// FAQ構造化データ用
$faq_items = array();

if ($grant['grant_target']) {
    $faq_items[] = array(
        'question' => 'この補助金の対象者は誰ですか？',
        'answer' => wp_strip_all_tags($grant['grant_target'])
    );
}

$docs = $grant['required_documents_detailed'] ?: $grant['required_documents'];
if ($docs) {
    $faq_items[] = array(
        'question' => '申請に必要な書類は何ですか？',
        'answer' => wp_strip_all_tags($docs)
    );
}

$expenses = $grant['eligible_expenses_detailed'] ?: $grant['eligible_expenses'];
if ($expenses) {
    $faq_items[] = array(
        'question' => 'どのような経費が対象になりますか？',
        'answer' => wp_strip_all_tags($expenses)
    );
}

// 申請チェックリスト項目
$checklist_items = array(
    array('id' => 'target', 'label' => '対象者の要件を満たしている', 'required' => true),
    array('id' => 'deadline', 'label' => '申請期限内である', 'required' => true),
    array('id' => 'area', 'label' => '対象地域に該当する', 'required' => !$is_nationwide),
    array('id' => 'documents', 'label' => '必要書類を準備できる', 'required' => true),
    array('id' => 'expenses', 'label' => '対象経費に該当する事業である', 'required' => true),
);

// おすすめ補助金取得
function get_ai_recommended_grants_v2($post_id, $taxonomies, $grant_data, $limit = 8) {
    $transient_key = 'gi_recommend_v2_' . $post_id;
    $cached = get_transient($transient_key);
    
    if (false !== $cached) {
        return $cached;
    }
    
    $candidates = new WP_Query(array(
        'post_type' => 'grant',
        'posts_per_page' => 100,
        'post__not_in' => array($post_id),
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => 'application_status',
                'value' => 'open',
                'compare' => '='
            )
        ),
    ));
    
    $scored = array();
    $current_pref_ids = wp_list_pluck($taxonomies['prefectures'], 'term_id');
    $current_muni_ids = wp_list_pluck($taxonomies['municipalities'], 'term_id');
    $current_cat_ids = wp_list_pluck($taxonomies['categories'], 'term_id');
    $current_ind_ids = wp_list_pluck($taxonomies['industries'], 'term_id');
    
    if ($candidates->have_posts()) {
        while ($candidates->have_posts()) {
            $candidates->the_post();
            $cid = get_the_ID();
            $score = 0;
            $match_reasons = array();
            
            $c_munis = wp_get_post_terms($cid, 'grant_municipality', array('fields' => 'ids'));
            if (!is_wp_error($c_munis) && count(array_intersect($c_munis, $current_muni_ids)) > 0) {
                $score += 500;
                $match_reasons[] = '同じ市町村';
            }
            
            $c_prefs = wp_get_post_terms($cid, 'grant_prefecture', array('fields' => 'ids'));
            if (!is_wp_error($c_prefs) && count(array_intersect($c_prefs, $current_pref_ids)) > 0) {
                $score += 300;
                $match_reasons[] = '同じ都道府県';
            }
            
            $c_inds = wp_get_post_terms($cid, 'grant_industry', array('fields' => 'ids'));
            if (!is_wp_error($c_inds) && count(array_intersect($c_inds, $current_ind_ids)) > 0) {
                $score += 250;
                $match_reasons[] = '同じ業種';
            }
            
            $c_cats = wp_get_post_terms($cid, 'grant_category', array('fields' => 'ids'));
            if (!is_wp_error($c_cats) && count(array_intersect($c_cats, $current_cat_ids)) > 0) {
                $score += 200;
                $match_reasons[] = '同じカテゴリ';
            }
            
            $c_amount = intval(get_field('max_amount_numeric', $cid));
            if ($c_amount > 0 && $grant_data['max_amount_numeric'] > 0) {
                $ratio = $c_amount / $grant_data['max_amount_numeric'];
                if ($ratio >= 0.5 && $ratio <= 2.0) {
                    $score += 100;
                    $match_reasons[] = '類似金額帯';
                }
            }
            
            if ($score >= 100) {
                $scored[] = array(
                    'id' => $cid,
                    'score' => $score,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'match_reasons' => array_slice($match_reasons, 0, 2),
                    'max_amount' => get_field('max_amount', $cid),
                    'deadline' => get_field('deadline', $cid),
                    'organization' => get_field('organization', $cid),
                );
            }
        }
        wp_reset_postdata();
    }
    
    usort($scored, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    $result = array_slice($scored, 0, $limit);
    set_transient($transient_key, $result, 12 * HOUR_IN_SECONDS);
    
    return $result;
}

$recommended = get_ai_recommended_grants_v2($post_id, $taxonomies, $grant, 8);

// 類似補助金
$similar_grants = array();
if (!empty($grant['similar_grants'])) {
    foreach ($grant['similar_grants'] as $similar_id) {
        if (get_post_status($similar_id) === 'publish') {
            $similar_grants[] = array(
                'id' => $similar_id,
                'title' => get_the_title($similar_id),
                'permalink' => get_permalink($similar_id),
                'max_amount' => get_field('max_amount', $similar_id),
                'max_amount_numeric' => intval(get_field('max_amount_numeric', $similar_id)),
                'subsidy_rate' => get_field('subsidy_rate', $similar_id),
                'deadline' => get_field('deadline', $similar_id),
                'difficulty' => get_field('grant_difficulty', $similar_id),
            );
        }
    }
}

// 関連コラム
$related_columns = new WP_Query(array(
    'post_type' => 'column',
    'posts_per_page' => 4,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'related_grants',
            'value' => '"' . $post_id . '"',
            'compare' => 'LIKE'
        )
    ),
));

// サイドバー用クエリ
$grant_ranking = new WP_Query(array(
    'post_type' => 'grant',
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'post__not_in' => array($post_id),
    'meta_key' => 'views_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
));

$grant_deadline_soon = new WP_Query(array(
    'post_type' => 'grant',
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'post__not_in' => array($post_id),
    'meta_query' => array(
        array(
            'key' => 'application_status',
            'value' => 'open',
        ),
        array(
            'key' => 'deadline_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        ),
        array(
            'key' => 'deadline_date',
            'value' => date('Y-m-d', strtotime('+30 days')),
            'compare' => '<=',
            'type' => 'DATE'
        ),
    ),
    'meta_key' => 'deadline_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
));

// 目次
$toc_items = array(
    array('id' => 'key-info', 'title' => '重要情報', 'icon' => 'star'),
    array('id' => 'summary', 'title' => 'AI要約', 'icon' => 'sparkles', 'show' => !empty($grant['ai_summary'])),
    array('id' => 'details', 'title' => '詳細情報', 'icon' => 'list'),
    array('id' => 'checklist', 'title' => '申請チェック', 'icon' => 'check'),
    array('id' => 'content', 'title' => '補助金概要', 'icon' => 'document'),
    array('id' => 'flow', 'title' => '申請の流れ', 'icon' => 'arrow', 'show' => !empty($grant['application_flow'])),
    array('id' => 'tips', 'title' => '申請のコツ', 'icon' => 'lightbulb', 'show' => !empty($grant['application_tips'])),
    array('id' => 'cases', 'title' => '採択事例', 'icon' => 'users', 'show' => !empty($grant['success_cases'])),
    array('id' => 'compare', 'title' => '類似補助金比較', 'icon' => 'compare', 'show' => !empty($similar_grants)),
    array('id' => 'faq', 'title' => 'よくある質問', 'icon' => 'question'),
    array('id' => 'recommended', 'title' => 'おすすめ補助金', 'icon' => 'recommend', 'show' => !empty($recommended)),
    array('id' => 'contact', 'title' => 'お問い合わせ', 'icon' => 'phone', 'show' => !empty($grant['contact_info']) || !empty($grant['contact_phone'])),
);

$toc_items = array_filter($toc_items, function($item) {
    return !isset($item['show']) || $item['show'];
});

// SEOプラグイン検出
$has_seo_plugin = (defined('WPSEO_VERSION') || defined('AIOSEO_VERSION') || class_exists('RankMath'));
?>

<?php if (!$has_seo_plugin): ?>
<link rel="canonical" href="<?php echo esc_url($canonical_url); ?>">
<meta name="description" content="<?php echo esc_attr($meta_desc); ?>">
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta property="og:title" content="<?php echo esc_attr($seo_title); ?>">
<meta property="og:description" content="<?php echo esc_attr($meta_desc); ?>">
<meta property="og:url" content="<?php echo esc_url($canonical_url); ?>">
<meta property="og:image" content="<?php echo esc_url($og_image); ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">
<meta property="og:locale" content="ja_JP">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr($seo_title); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($meta_desc); ?>">
<meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">
<?php endif; ?>

<!-- 構造化データ -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@graph": [
        {
            "@type": "BreadcrumbList",
            "itemListElement": [
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                {
                    "@type": "ListItem",
                    "position": <?php echo $index + 1; ?>,
                    "name": <?php echo json_encode($crumb['name']); ?>,
                    "item": "<?php echo esc_js($crumb['url']); ?>"
                }<?php echo $index < count($breadcrumbs) - 1 ? ',' : ''; ?>
                <?php endforeach; ?>
            ]
        },
        {
            "@type": "MonetaryGrant",
            "name": <?php echo json_encode(get_the_title()); ?>,
            "description": <?php echo json_encode($meta_desc); ?>,
            "url": "<?php echo esc_js($canonical_url); ?>",
            "funder": {
                "@type": "Organization",
                "name": <?php echo json_encode($grant['organization'] ?: $site_name); ?>
            },
            <?php if ($grant['max_amount_numeric'] > 0): ?>
            "amount": {
                "@type": "MonetaryAmount",
                "currency": "JPY",
                "value": "<?php echo $grant['max_amount_numeric']; ?>"
            },
            <?php endif; ?>
            "datePublished": "<?php echo get_the_date('c'); ?>",
            "dateModified": "<?php echo get_the_modified_date('c'); ?>"
        }
        <?php if (!empty($faq_items)): ?>
        ,{
            "@type": "FAQPage",
            "mainEntity": [
                <?php foreach ($faq_items as $index => $faq): ?>
                {
                    "@type": "Question",
                    "name": <?php echo json_encode($faq['question']); ?>,
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": <?php echo json_encode($faq['answer']); ?>
                    }
                }<?php echo $index < count($faq_items) - 1 ? ',' : ''; ?>
                <?php endforeach; ?>
            ]
        }
        <?php endif; ?>
    ]
}
</script>

<style>
/* ===================================
   Ultimate Design System v201
   =================================== */

:root {
    --black: #0A0A0A;
    --white: #FFFFFF;
    --gray-25: #FCFCFC;
    --gray-50: #FAFAFA;
    --gray-100: #F5F5F5;
    --gray-200: #E5E5E5;
    --gray-300: #D4D4D4;
    --gray-400: #A3A3A3;
    --gray-500: #737373;
    --gray-600: #525252;
    --gray-700: #404040;
    --gray-800: #262626;
    --gray-900: #171717;
    
    --primary: #0A0A0A;
    --primary-hover: #262626;
    --accent: #FBBF24;
    --accent-light: #FEF3C7;
    --accent-dark: #D97706;
    --success: #059669;
    --success-light: #D1FAE5;
    --success-dark: #047857;
    --warning: #D97706;
    --warning-light: #FEF3C7;
    --error: #DC2626;
    --error-light: #FEE2E2;
    --info: #2563EB;
    --info-light: #DBEAFE;
    
    --marker-critical: #FEE2E2;
    --marker-important: #FEF3C7;
    --marker-normal: #E0F2FE;
    --marker-success: #D1FAE5;
    
    --text-xs: 0.75rem;
    --text-sm: 0.875rem;
    --text-base: 1rem;
    --text-lg: 1.125rem;
    --text-xl: 1.25rem;
    --text-2xl: 1.5rem;
    --text-3xl: 1.875rem;
    --text-4xl: 2.25rem;
    --text-5xl: 3rem;
    
    --leading-tight: 1.4;
    --leading-normal: 1.75;
    --leading-relaxed: 1.9;
    --leading-loose: 2.1;
    
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    --space-10: 2.5rem;
    --space-12: 3rem;
    --space-16: 4rem;
    --space-20: 5rem;
    --space-24: 6rem;
    
    --container-max: 1400px;
    --content-max: 780px;
    --sidebar-width: 360px;
    --gap: 48px;
    
    --radius-sm: 4px;
    --radius: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
    --radius-full: 9999px;
    
    --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.04);
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
    --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
    --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    
    --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
    --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
    
    --focus-ring: 0 0 0 3px rgba(251, 191, 36, 0.5);
}

*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    font-size: 16px;
    scroll-behavior: smooth;
    -webkit-text-size-adjust: 100%;
}

@media (max-width: 768px) {
    html { font-size: 15px; }
}

body {
    font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', 'Yu Gothic', sans-serif;
    font-size: var(--text-base);
    line-height: var(--leading-normal);
    color: var(--gray-900);
    background: var(--white);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow-x: hidden;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

:focus-visible {
    outline: none;
    box-shadow: var(--focus-ring);
}

.skip-link {
    position: absolute;
    top: -100%;
    left: 50%;
    transform: translateX(-50%);
    background: var(--primary);
    color: var(--white);
    padding: var(--space-3) var(--space-6);
    border-radius: var(--radius);
    z-index: 10000;
    transition: var(--transition);
}

.skip-link:focus { top: var(--space-4); }

.gi-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent) 0%, var(--accent-dark) 100%);
    z-index: 9999;
    transition: width 50ms linear;
}

/* パンくず */
.gi-breadcrumb {
    padding: var(--space-4) 0;
    font-size: var(--text-sm);
    color: var(--gray-500);
    border-bottom: 1px solid var(--gray-100);
    margin-bottom: var(--space-8);
}

.gi-breadcrumb-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
    list-style: none;
    max-width: var(--container-max);
    margin: 0 auto;
    padding: 0 var(--space-6);
}

.gi-breadcrumb-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.gi-breadcrumb-link {
    color: var(--gray-500);
    text-decoration: none;
    transition: var(--transition);
}

.gi-breadcrumb-link:hover { color: var(--primary); }

.gi-breadcrumb-sep {
    color: var(--gray-300);
    user-select: none;
}

.gi-breadcrumb-current {
    color: var(--gray-700);
    font-weight: 500;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* コンテナ */
.gi-container {
    max-width: var(--container-max);
    margin: 0 auto;
    padding: 0 var(--space-6);
}

.gi-layout {
    display: grid;
    grid-template-columns: 1fr var(--sidebar-width);
    gap: var(--gap);
    align-items: start;
}

.gi-main {
    min-width: 0;
    max-width: var(--content-max);
}

.gi-sidebar {
    position: sticky;
    top: 24px;
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

@media (max-width: 1200px) {
    .gi-layout {
        grid-template-columns: 1fr 320px;
        gap: 32px;
    }
}

@media (max-width: 1024px) {
    .gi-layout { grid-template-columns: 1fr; }
    .gi-sidebar { display: none; }
    .gi-main { max-width: 100%; }
}

/* ヒーロー */
.gi-hero { margin-bottom: var(--space-10); }

.gi-hero-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-4);
}

.gi-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-1) var(--space-3);
    font-size: var(--text-xs);
    font-weight: 700;
    line-height: 1.5;
    border-radius: var(--radius-full);
    white-space: nowrap;
}

.gi-badge-status {
    padding: var(--space-2) var(--space-4);
    font-size: var(--text-sm);
}

.gi-badge-open { background: var(--success); color: var(--white); }
.gi-badge-closed { background: var(--gray-400); color: var(--white); }
.gi-badge-upcoming { background: var(--info); color: var(--white); }
.gi-badge-suspended { background: var(--warning); color: var(--white); }

.gi-badge-deadline {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1px solid var(--gray-200);
}

.gi-badge-deadline.critical {
    background: var(--error);
    color: var(--white);
    animation: pulse 1.5s infinite;
}

.gi-badge-deadline.urgent {
    background: var(--error-light);
    color: var(--error);
    border-color: var(--error);
}

.gi-badge-deadline.warning {
    background: var(--warning-light);
    color: var(--warning);
    border-color: var(--warning);
}

.gi-badge-featured {
    background: var(--accent);
    color: var(--gray-900);
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.85; transform: scale(1.02); }
}

.gi-hero-title {
    font-size: var(--text-4xl);
    font-weight: 800;
    line-height: var(--leading-tight);
    color: var(--gray-900);
    margin-bottom: var(--space-5);
    letter-spacing: -0.02em;
}

@media (max-width: 768px) {
    .gi-hero-title { font-size: var(--text-2xl); }
}

.gi-hero-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-4);
    font-size: var(--text-sm);
    color: var(--gray-500);
}

.gi-hero-meta-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.gi-hero-meta-item svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

/* キーインフォ */
.gi-key-info {
    background: linear-gradient(135deg, var(--gray-25) 0%, var(--white) 100%);
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-8);
    margin-bottom: var(--space-8);
    position: relative;
    overflow: hidden;
}

.gi-key-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent) 0%, var(--accent-dark) 100%);
}

.gi-key-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-6);
}

@media (max-width: 768px) {
    .gi-key-info-grid {
        grid-template-columns: 1fr;
        gap: var(--space-4);
    }
}

.gi-key-item {
    text-align: center;
    padding: var(--space-4);
}

.gi-key-item-label {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--gray-500);
    margin-bottom: var(--space-2);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.gi-key-item-value {
    font-size: var(--text-3xl);
    font-weight: 800;
    color: var(--gray-900);
    line-height: 1.2;
}

.gi-key-item-value.highlight {
    background: linear-gradient(180deg, transparent 50%, var(--accent-light) 50%);
    display: inline;
    padding: 0 var(--space-2);
}

.gi-key-item-sub {
    font-size: var(--text-sm);
    color: var(--gray-600);
    margin-top: var(--space-1);
}

.gi-key-item-value.amount { color: var(--success-dark); }
.gi-key-item-value.deadline { color: var(--gray-900); }
.gi-key-item-value.deadline.critical,
.gi-key-item-value.deadline.urgent { color: var(--error); }
.gi-key-item-value.deadline.warning { color: var(--warning); }

.gi-countdown {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: var(--space-1);
}

.gi-countdown-number {
    font-size: var(--text-5xl);
    font-weight: 900;
    line-height: 1;
}

.gi-countdown-unit {
    font-size: var(--text-lg);
    font-weight: 600;
}

/* カード */
.gi-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-8);
    overflow: hidden;
    transition: var(--transition);
}

.gi-card:hover {
    border-color: var(--gray-300);
    box-shadow: var(--shadow);
}

.gi-card-header {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-5) var(--space-6);
    border-bottom: 1px solid var(--gray-100);
    background: var(--gray-25);
}

.gi-card-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    color: var(--accent-dark);
}

.gi-card-title {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.gi-card-body { padding: var(--space-6); }

.gi-card-content {
    font-size: var(--text-base);
    line-height: var(--leading-relaxed);
    color: var(--gray-700);
}

/* AI要約カード */
.gi-summary-card {
    border-color: var(--accent);
    background: linear-gradient(135deg, var(--accent-light) 0%, var(--white) 100%);
}

.gi-summary-card .gi-card-header {
    background: transparent;
    border-bottom-color: var(--accent);
}

.gi-summary-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    padding: var(--space-1) var(--space-2);
    background: var(--accent);
    color: var(--gray-900);
    font-size: var(--text-xs);
    font-weight: 700;
    border-radius: var(--radius-sm);
    margin-left: auto;
}

.gi-summary-text {
    font-size: var(--text-lg);
    line-height: var(--leading-loose);
    color: var(--gray-800);
}

/* テーブル */
.gi-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.gi-table-row {
    display: flex;
    border-bottom: 1px solid var(--gray-100);
}

.gi-table-row:last-child { border-bottom: none; }
.gi-table-row:hover { background: var(--gray-25); }

.gi-table-key {
    flex: 0 0 180px;
    padding: var(--space-5) var(--space-4);
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--gray-600);
    background: var(--gray-50);
    display: flex;
    align-items: flex-start;
}

.gi-table-value {
    flex: 1;
    padding: var(--space-5) var(--space-4);
    font-size: var(--text-base);
    line-height: var(--leading-relaxed);
    color: var(--gray-800);
}

@media (max-width: 640px) {
    .gi-table-row { flex-direction: column; }
    .gi-table-key {
        flex: none;
        padding: var(--space-3) var(--space-4);
        padding-bottom: 0;
        background: transparent;
    }
    .gi-table-value {
        padding: var(--space-2) var(--space-4);
        padding-bottom: var(--space-4);
    }
}

.gi-value-large {
    font-size: var(--text-2xl);
    font-weight: 800;
    color: var(--gray-900);
}

.gi-value-highlight {
    background: linear-gradient(180deg, transparent 60%, var(--marker-important) 60%);
    padding: 0 var(--space-1);
}

.gi-value-success { color: var(--success-dark); }
.gi-value-warning { color: var(--warning); }
.gi-value-error { color: var(--error); }

.gi-tag {
    display: inline-flex;
    align-items: center;
    padding: var(--space-1) var(--space-3);
    margin: var(--space-1);
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: var(--text-sm);
    color: var(--gray-700);
    text-decoration: none;
    transition: var(--transition);
}

.gi-tag:hover {
    background: var(--primary);
    border-color: var(--primary);
    color: var(--white);
}

.gi-tag-more {
    display: inline-flex;
    padding: var(--space-1) var(--space-3);
    margin: var(--space-1);
    background: var(--gray-200);
    border-radius: var(--radius);
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--gray-600);
}

.gi-difficulty {
    display: flex;
    align-items: center;
    gap: var(--space-3);
}

.gi-difficulty-label {
    font-weight: 700;
    color: var(--gray-900);
}

.gi-difficulty-stars {
    display: flex;
    gap: 2px;
}

.gi-difficulty-star {
    width: 16px;
    height: 16px;
    color: var(--gray-300);
}

.gi-difficulty-star.active { color: var(--accent); }

.gi-difficulty-desc {
    font-size: var(--text-sm);
    color: var(--gray-500);
}

/* チェックリスト */
.gi-checklist-card { border-color: var(--success); }

.gi-checklist-card .gi-card-header {
    background: var(--success-light);
    border-bottom-color: var(--success);
}

.gi-checklist-progress {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4) var(--space-6);
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-100);
}

.gi-checklist-progress-bar {
    flex: 1;
    height: 8px;
    background: var(--gray-200);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.gi-checklist-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--success) 0%, var(--success-dark) 100%);
    border-radius: var(--radius-full);
    transition: width 0.3s ease;
    width: 0;
}

.gi-checklist-progress-text {
    font-size: var(--text-sm);
    font-weight: 700;
    color: var(--gray-700);
    white-space: nowrap;
}

.gi-checklist-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.gi-checklist-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-6);
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: var(--transition);
}

.gi-checklist-item:last-child { border-bottom: none; }
.gi-checklist-item:hover { background: var(--gray-25); }
.gi-checklist-item.checked { background: var(--success-light); }

.gi-checklist-checkbox {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    border: 2px solid var(--gray-300);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    cursor: pointer;
}

.gi-checklist-item.checked .gi-checklist-checkbox {
    background: var(--success);
    border-color: var(--success);
}

.gi-checklist-checkbox svg {
    width: 14px;
    height: 14px;
    color: var(--white);
    opacity: 0;
    transition: var(--transition);
}

.gi-checklist-item.checked .gi-checklist-checkbox svg { opacity: 1; }

.gi-checklist-label {
    flex: 1;
    font-size: var(--text-base);
    color: var(--gray-700);
    line-height: var(--leading-normal);
}

.gi-checklist-item.checked .gi-checklist-label { color: var(--success-dark); }

.gi-checklist-required {
    font-size: var(--text-xs);
    color: var(--error);
    font-weight: 600;
}

.gi-checklist-result {
    padding: var(--space-5) var(--space-6);
    background: var(--gray-50);
    text-align: center;
}

.gi-checklist-result-text {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--gray-700);
}

.gi-checklist-result-text.complete { color: var(--success-dark); }

/* コンテンツ */
.gi-content {
    font-size: var(--text-base);
    line-height: var(--leading-loose);
    color: var(--gray-700);
}

.gi-content h2 {
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--gray-900);
    margin: var(--space-10) 0 var(--space-5);
    padding-bottom: var(--space-3);
    border-bottom: 2px solid var(--gray-200);
}

.gi-content h3 {
    font-size: var(--text-xl);
    font-weight: 700;
    color: var(--gray-900);
    margin: var(--space-8) 0 var(--space-4);
}

.gi-content h4 {
    font-size: var(--text-lg);
    font-weight: 600;
    color: var(--gray-800);
    margin: var(--space-6) 0 var(--space-3);
}

.gi-content p { margin-bottom: var(--space-5); }

.gi-content ul, .gi-content ol {
    margin: var(--space-4) 0;
    padding-left: var(--space-6);
}

.gi-content li { margin-bottom: var(--space-2); }

.gi-content a {
    color: var(--info);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.gi-content a:hover { color: var(--primary); }

.gi-content strong {
    font-weight: 700;
    color: var(--gray-900);
}

.gi-content blockquote {
    margin: var(--space-6) 0;
    padding: var(--space-5) var(--space-6);
    background: var(--gray-50);
    border-left: 4px solid var(--accent);
    border-radius: 0 var(--radius) var(--radius) 0;
}

/* 申請フロー */
.gi-flow {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.gi-flow-step {
    display: flex;
    gap: var(--space-4);
    position: relative;
}

.gi-flow-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 19px;
    top: 48px;
    bottom: -8px;
    width: 2px;
    background: var(--gray-200);
}

.gi-flow-number {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    background: var(--primary);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-lg);
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.gi-flow-content {
    flex: 1;
    padding-bottom: var(--space-6);
}

.gi-flow-title {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--space-2);
}

.gi-flow-desc {
    font-size: var(--text-base);
    color: var(--gray-600);
    line-height: var(--leading-relaxed);
}

/* 採択事例 */
.gi-cases-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--space-5);
}

.gi-case-card {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    padding: var(--space-5);
    transition: var(--transition);
}

.gi-case-card:hover {
    border-color: var(--accent);
    box-shadow: var(--shadow);
}

.gi-case-header {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.gi-case-icon {
    width: 48px;
    height: 48px;
    background: var(--accent-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gi-case-icon svg {
    width: 24px;
    height: 24px;
    color: var(--accent-dark);
}

.gi-case-meta { flex: 1; }

.gi-case-industry {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--gray-600);
}

.gi-case-amount {
    font-size: var(--text-lg);
    font-weight: 800;
    color: var(--success-dark);
}

.gi-case-purpose {
    font-size: var(--text-sm);
    color: var(--gray-600);
    line-height: var(--leading-relaxed);
}

/* 比較テーブル */
.gi-compare-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: var(--text-sm);
}

.gi-compare-table th,
.gi-compare-table td {
    padding: var(--space-4);
    text-align: center;
    border-bottom: 1px solid var(--gray-200);
}

.gi-compare-table th {
    background: var(--gray-50);
    font-weight: 600;
    color: var(--gray-600);
}

.gi-compare-table th:first-child,
.gi-compare-table td:first-child {
    text-align: left;
    font-weight: 600;
    background: var(--gray-50);
}

.gi-compare-current { background: var(--accent-light) !important; }

.gi-compare-current-header {
    background: var(--accent) !important;
    font-weight: 700 !important;
}

.gi-compare-link {
    color: var(--info);
    text-decoration: none;
}

.gi-compare-link:hover { text-decoration: underline; }

/* FAQ */
.gi-faq-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.gi-faq-item {
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    overflow: hidden;
    transition: var(--transition);
}

.gi-faq-item:hover { border-color: var(--gray-300); }
.gi-faq-item[open] { border-color: var(--primary); }

.gi-faq-question {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-5);
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--gray-800);
    cursor: pointer;
    list-style: none;
    transition: var(--transition);
}

.gi-faq-question::-webkit-details-marker { display: none; }
.gi-faq-question:hover { background: var(--gray-25); }

.gi-faq-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    transition: transform 0.2s ease;
}

.gi-faq-item[open] .gi-faq-icon { transform: rotate(45deg); }

.gi-faq-answer {
    padding: 0 var(--space-5) var(--space-5);
    font-size: var(--text-base);
    line-height: var(--leading-relaxed);
    color: var(--gray-600);
}

/* おすすめ補助金 */
.gi-recommend-section {
    margin: var(--space-12) 0;
    padding: var(--space-12) 0;
    background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 100%);
    border-top: 1px solid var(--gray-200);
    border-bottom: 1px solid var(--gray-200);
}

.gi-recommend-header {
    text-align: center;
    margin-bottom: var(--space-10);
}

.gi-recommend-header h2 {
    font-size: var(--text-3xl);
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: var(--space-3);
}

.gi-recommend-header p {
    font-size: var(--text-lg);
    color: var(--gray-500);
}

.gi-recommend-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--space-5);
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--space-6);
}

.gi-recommend-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    text-decoration: none;
    transition: var(--transition);
    display: block;
}

.gi-recommend-card:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.gi-recommend-badges {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
}

.gi-recommend-badge {
    padding: var(--space-1) var(--space-2);
    background: var(--gray-100);
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--gray-600);
}

.gi-recommend-badge.match {
    background: var(--accent-light);
    color: var(--accent-dark);
}

.gi-recommend-title {
    font-size: var(--text-base);
    font-weight: 700;
    color: var(--gray-900);
    line-height: var(--leading-normal);
    margin-bottom: var(--space-3);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.gi-recommend-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-4);
    font-size: var(--text-sm);
    color: var(--gray-500);
}

.gi-recommend-meta-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.gi-recommend-meta-label {
    font-size: var(--text-xs);
    color: var(--gray-400);
}

.gi-recommend-meta-value {
    font-weight: 600;
    color: var(--gray-700);
}

/* ===================================
   CTA - 修正版（白文字）
   =================================== */
.gi-cta {
    background: var(--primary);
    color: var(--white);
    border-radius: var(--radius-xl);
    padding: var(--space-16) var(--space-8);
    text-align: center;
    margin: var(--space-12) 0;
}

.gi-cta h2 {
    font-size: var(--text-3xl);
    font-weight: 800;
    margin-bottom: var(--space-4);
    color: var(--white); /* 明示的に白色指定 */
}

.gi-cta p {
    font-size: var(--text-lg);
    color: rgba(255, 255, 255, 0.8); /* 白の80%透明度 */
    margin-bottom: var(--space-8);
}

.gi-cta-buttons {
    display: flex;
    justify-content: center;
    gap: var(--space-4);
    flex-wrap: wrap;
}

/* ボタン */
.gi-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    padding: var(--space-4) var(--space-6);
    font-size: var(--text-base);
    font-weight: 700;
    text-decoration: none;
    border: 2px solid transparent;
    border-radius: var(--radius);
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
    line-height: 1;
    min-height: 48px;
}

.gi-btn:focus-visible { box-shadow: var(--focus-ring); }

.gi-btn-primary {
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
}

.gi-btn-primary:hover {
    background: var(--gray-800);
    border-color: var(--gray-800);
}

.gi-btn-secondary {
    background: var(--white);
    color: var(--primary);
    border-color: var(--gray-300);
}

.gi-btn-secondary:hover {
    border-color: var(--primary);
    background: var(--gray-50);
}

.gi-btn-accent {
    background: var(--accent);
    color: var(--gray-900);
    border-color: var(--accent);
}

.gi-btn-accent:hover {
    background: var(--accent-dark);
    border-color: var(--accent-dark);
    color: var(--white);
}

.gi-btn-white {
    background: var(--white);
    color: var(--primary);
    border-color: var(--white);
}

.gi-btn-white:hover {
    background: var(--gray-100);
    border-color: var(--gray-100);
}

.gi-btn-outline-white {
    background: transparent;
    color: var(--white);
    border-color: var(--white);
}

.gi-btn-outline-white:hover {
    background: var(--white);
    color: var(--primary);
}

.gi-btn-lg {
    padding: var(--space-5) var(--space-8);
    font-size: var(--text-lg);
    min-height: 56px;
}

.gi-btn-full { width: 100%; }

.gi-btn svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* 情報ソース・監修者 */
.gi-source-card {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    padding: var(--space-5);
    margin-top: var(--space-8);
}

.gi-source-header {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--gray-600);
}

.gi-source-header svg {
    width: 16px;
    height: 16px;
}

.gi-source-content {
    font-size: var(--text-sm);
    color: var(--gray-600);
    line-height: var(--leading-relaxed);
}

.gi-source-link {
    color: var(--info);
    text-decoration: none;
}

.gi-source-link:hover { text-decoration: underline; }

.gi-supervisor-card {
    background: var(--white);
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    margin-top: var(--space-8);
}

.gi-supervisor-header {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-4);
    font-size: var(--text-sm);
    font-weight: 700;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.gi-supervisor-content {
    display: flex;
    gap: var(--space-5);
    align-items: flex-start;
}

.gi-supervisor-image {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: var(--gray-200);
    flex-shrink: 0;
    overflow: hidden;
}

.gi-supervisor-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gi-supervisor-info { flex: 1; }

.gi-supervisor-name {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: var(--space-1);
}

.gi-supervisor-title {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--accent-dark);
    margin-bottom: var(--space-3);
}

.gi-supervisor-profile {
    font-size: var(--text-sm);
    color: var(--gray-600);
    line-height: var(--leading-relaxed);
}

/* サイドバー */
.gi-sidebar-card {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: var(--transition);
}

.gi-sidebar-card:hover {
    border-color: var(--gray-300);
    box-shadow: var(--shadow-sm);
}

.gi-sidebar-header {
    padding: var(--space-4) var(--space-5);
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-100);
}

.gi-sidebar-title {
    font-size: var(--text-sm);
    font-weight: 700;
    color: var(--gray-700);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin: 0;
}

.gi-sidebar-title svg {
    width: 16px;
    height: 16px;
    color: var(--accent-dark);
}

.gi-sidebar-body { padding: var(--space-4); }

/* TOC */
.gi-toc-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.gi-toc-item { margin-bottom: 0; }

.gi-toc-link {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    color: var(--gray-600);
    text-decoration: none;
    font-size: var(--text-sm);
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: var(--transition);
}

.gi-toc-link:hover {
    color: var(--gray-900);
    background: var(--gray-50);
}

.gi-toc-link.active {
    color: var(--gray-900);
    background: var(--accent-light);
    border-left-color: var(--accent);
    font-weight: 700;
}

/* サイドバーリスト */
.gi-sidebar-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.gi-sidebar-list-item { border-bottom: 1px solid var(--gray-100); }
.gi-sidebar-list-item:last-child { border-bottom: none; }

.gi-sidebar-list-link {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-4);
    text-decoration: none;
    transition: var(--transition);
}

.gi-sidebar-list-link:hover { background: var(--gray-50); }

.gi-sidebar-list-rank {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    background: var(--gray-200);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-xs);
    font-weight: 700;
    color: var(--gray-600);
}

.gi-sidebar-list-rank.gold { background: var(--accent); color: var(--gray-900); }
.gi-sidebar-list-rank.silver { background: var(--gray-300); color: var(--gray-700); }
.gi-sidebar-list-rank.bronze { background: #CD7F32; color: var(--white); }

.gi-sidebar-list-content {
    flex: 1;
    min-width: 0;
}

.gi-sidebar-list-title {
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--gray-800);
    line-height: var(--leading-normal);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.gi-sidebar-list-meta {
    font-size: var(--text-xs);
    color: var(--gray-500);
    margin-top: var(--space-1);
}

/* ===================================
   PC版AIアシスタント（サイドバー）
   =================================== */
.gi-ai-sidebar-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--gray-800) 100%);
    border: none;
    color: var(--white);
}

.gi-ai-sidebar-card .gi-sidebar-header {
    background: transparent;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.gi-ai-sidebar-card .gi-sidebar-title {
    color: var(--white);
}

.gi-ai-sidebar-card .gi-sidebar-title svg {
    color: var(--accent);
}

.gi-ai-sidebar-body {
    padding: var(--space-4);
}

.gi-ai-pc-messages {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.gi-ai-pc-message {
    display: flex;
    gap: var(--space-2);
    max-width: 95%;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.gi-ai-pc-message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.gi-ai-pc-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 10px;
    font-weight: 700;
    background: var(--accent);
    color: var(--gray-900);
}

.gi-ai-pc-message.user .gi-ai-pc-avatar {
    background: var(--gray-600);
    color: var(--white);
}

.gi-ai-pc-bubble {
    padding: var(--space-3);
    border-radius: var(--radius);
    font-size: var(--text-sm);
    line-height: var(--leading-relaxed);
    background: rgba(255, 255, 255, 0.1);
    color: var(--white);
}

.gi-ai-pc-message.user .gi-ai-pc-bubble {
    background: var(--accent);
    color: var(--gray-900);
}

.gi-ai-pc-input-wrapper {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
}

.gi-ai-pc-input {
    flex: 1;
    padding: var(--space-3);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: var(--radius);
    font-size: var(--text-sm);
    font-family: inherit;
    resize: none;
    min-height: 40px;
    max-height: 80px;
    background: rgba(255, 255, 255, 0.1);
    color: var(--white);
    transition: var(--transition);
}

.gi-ai-pc-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.gi-ai-pc-input:focus {
    outline: none;
    border-color: var(--accent);
    background: rgba(255, 255, 255, 0.15);
}

.gi-ai-pc-send {
    width: 40px;
    height: 40px;
    background: var(--accent);
    color: var(--gray-900);
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    flex-shrink: 0;
}

.gi-ai-pc-send:hover:not(:disabled) {
    background: var(--accent-dark);
    color: var(--white);
}

.gi-ai-pc-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.gi-ai-pc-send svg {
    width: 16px;
    height: 16px;
}

.gi-ai-pc-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.gi-ai-pc-chip {
    padding: var(--space-1) var(--space-3);
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: var(--transition);
}

.gi-ai-pc-chip:hover {
    background: var(--accent);
    border-color: var(--accent);
    color: var(--gray-900);
}

/* モバイル固定ボタン */
.gi-mobile-fixed { display: none; }

@media (max-width: 1024px) {
    .gi-mobile-fixed {
        display: flex;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--white);
        border-top: 1px solid var(--gray-200);
        padding: var(--space-3) var(--space-4);
        gap: var(--space-3);
        z-index: 100;
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .gi-mobile-fixed .gi-btn { flex: 1; }
    .gi-main { padding-bottom: 100px; }
}

/* モバイルAIボタン */
.gi-mobile-ai-btn { display: none; }

@media (max-width: 1024px) {
    .gi-mobile-ai-btn {
        display: flex;
        position: fixed;
        bottom: 100px;
        right: 16px;
        z-index: 99;
        background: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        cursor: pointer;
        box-shadow: var(--shadow-lg);
        transition: var(--transition);
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2px;
    }
    
    .gi-mobile-ai-btn:hover {
        transform: scale(1.05);
        box-shadow: var(--shadow-xl);
    }
    
    .gi-mobile-ai-btn svg {
        width: 24px;
        height: 24px;
    }
    
    .gi-mobile-ai-btn-text {
        font-size: 9px;
        font-weight: 700;
    }
}

/* モバイルパネル */
.gi-mobile-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    opacity: 0;
    visibility: hidden;
    transition: var(--transition);
}

.gi-mobile-overlay.active {
    opacity: 1;
    visibility: visible;
}

.gi-mobile-panel {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--white);
    border-top-left-radius: var(--radius-xl);
    border-top-right-radius: var(--radius-xl);
    max-height: 85vh;
    overflow: hidden;
    z-index: 999;
    transform: translateY(100%);
    visibility: hidden;
    transition: transform 0.3s ease, visibility 0.3s ease;
    box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.2);
}

.gi-mobile-panel.active {
    transform: translateY(0);
    visibility: visible;
}

@media (max-width: 1024px) {
    .gi-mobile-overlay,
    .gi-mobile-panel { display: block; }
}

.gi-mobile-panel-handle {
    width: 40px;
    height: 4px;
    background: var(--gray-300);
    border-radius: var(--radius-full);
    margin: var(--space-3) auto;
}

.gi-mobile-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4) var(--space-5);
    border-bottom: 1px solid var(--gray-200);
}

.gi-mobile-panel-title {
    font-size: var(--text-lg);
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.gi-mobile-panel-close {
    width: 40px;
    height: 40px;
    background: var(--gray-100);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}

.gi-mobile-panel-close:hover { background: var(--gray-200); }

.gi-mobile-panel-close svg {
    width: 20px;
    height: 20px;
    color: var(--gray-600);
}

.gi-mobile-tabs {
    display: flex;
    border-bottom: 1px solid var(--gray-200);
}

.gi-mobile-tab {
    flex: 1;
    padding: var(--space-4);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: var(--text-sm);
    font-weight: 600;
    color: var(--gray-500);
    cursor: pointer;
    transition: var(--transition);
    margin-bottom: -1px;
}

.gi-mobile-tab.active {
    color: var(--gray-900);
    border-bottom-color: var(--accent);
}

.gi-mobile-content {
    display: none;
    padding: var(--space-5);
    max-height: calc(85vh - 150px);
    overflow-y: auto;
}

.gi-mobile-content.active { display: block; }

/* モバイルAIチャット */
.gi-ai-messages {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
    max-height: 300px;
    overflow-y: auto;
}

.gi-ai-message {
    display: flex;
    gap: var(--space-3);
    max-width: 85%;
    animation: slideIn 0.3s ease;
}

.gi-ai-message.user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.gi-ai-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: var(--text-xs);
    font-weight: 700;
    background: var(--primary);
    color: var(--white);
}

.gi-ai-message.user .gi-ai-avatar {
    background: var(--gray-300);
    color: var(--gray-700);
}

.gi-ai-bubble {
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-lg);
    font-size: var(--text-sm);
    line-height: var(--leading-relaxed);
    background: var(--gray-100);
    color: var(--gray-800);
}

.gi-ai-message.user .gi-ai-bubble {
    background: var(--primary);
    color: var(--white);
}

.gi-ai-input-wrapper {
    display: flex;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.gi-ai-input {
    flex: 1;
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--gray-300);
    border-radius: var(--radius);
    font-size: 16px;
    font-family: inherit;
    resize: none;
    min-height: 48px;
    max-height: 100px;
    transition: var(--transition);
}

.gi-ai-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: var(--focus-ring);
}

.gi-ai-send {
    width: 48px;
    height: 48px;
    background: var(--accent);
    color: var(--gray-900);
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    flex-shrink: 0;
}

.gi-ai-send:hover:not(:disabled) {
    background: var(--accent-dark);
    color: var(--white);
}

.gi-ai-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.gi-ai-send svg {
    width: 20px;
    height: 20px;
}

.gi-ai-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}

.gi-ai-chip {
    padding: var(--space-2) var(--space-3);
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: 600;
    color: var(--gray-600);
    cursor: pointer;
    transition: var(--transition);
}

.gi-ai-chip:hover {
    background: var(--accent-light);
    border-color: var(--accent);
    color: var(--accent-dark);
}

/* 印刷スタイル */
@media print {
    .gi-sidebar,
    .gi-mobile-fixed,
    .gi-mobile-ai-btn,
    .gi-mobile-overlay,
    .gi-mobile-panel,
    .gi-progress,
    .gi-cta,
    .gi-recommend-section { display: none !important; }
    
    .gi-layout { grid-template-columns: 1fr; }
    .gi-main { max-width: 100%; }
    .gi-card { break-inside: avoid; border: 1px solid #000; }
    .gi-hero-title { font-size: 24pt; }
    body { font-size: 12pt; line-height: 1.6; }
}
</style>

<a href="#main-content" class="skip-link">メインコンテンツへスキップ</a>

<div class="gi-progress" id="progressBar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="読了進捗"></div>

<nav class="gi-breadcrumb" aria-label="パンくずリスト">
    <ol class="gi-breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
        <li class="gi-breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <?php if ($index < count($breadcrumbs) - 1): ?>
            <a href="<?php echo esc_url($crumb['url']); ?>" class="gi-breadcrumb-link" itemprop="item">
                <span itemprop="name"><?php echo esc_html($crumb['name']); ?></span>
            </a>
            <span class="gi-breadcrumb-sep" aria-hidden="true">›</span>
            <?php else: ?>
            <span class="gi-breadcrumb-current" itemprop="name"><?php echo esc_html($crumb['name']); ?></span>
            <?php endif; ?>
            <meta itemprop="position" content="<?php echo $index + 1; ?>">
        </li>
        <?php endforeach; ?>
    </ol>
</nav>

<div class="gi-container">
    <div class="gi-layout">
        <main id="main-content" class="gi-main" role="main" itemscope itemtype="https://schema.org/Article">
            
            <!-- ヒーローエリア -->
            <header class="gi-hero">
                <div class="gi-hero-badges">
                    <span class="gi-badge gi-badge-status gi-badge-<?php echo $status['class']; ?>">
                        <?php echo $status['icon']; ?> <?php echo $status['label']; ?>
                    </span>
                    
                    <?php if ($days_remaining > 0 && $days_remaining <= 30): ?>
                    <span class="gi-badge gi-badge-deadline <?php echo $deadline_class; ?>">
                        残り<?php echo $days_remaining; ?>日
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($grant['is_featured']): ?>
                    <span class="gi-badge gi-badge-featured">★ 注目</span>
                    <?php endif; ?>
                </div>
                
                <h1 class="gi-hero-title" itemprop="headline"><?php the_title(); ?></h1>
                
                <div class="gi-hero-meta">
                    <span class="gi-hero-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>読了 約<?php echo $reading_time; ?>分</span>
                    </span>
                    
                    <span class="gi-hero-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <span><?php echo number_format($grant['views_count']); ?>回閲覧</span>
                    </span>
                    
                    <time datetime="<?php echo get_the_modified_date('c'); ?>" itemprop="dateModified">
                        更新: <?php echo get_the_modified_date('Y年n月j日'); ?>
                    </time>
                </div>
            </header>

            <!-- キーインフォ -->
            <section id="key-info" class="gi-key-info" aria-labelledby="key-info-title">
                <h2 id="key-info-title" class="sr-only">重要情報</h2>
                <div class="gi-key-info-grid">
                    <div class="gi-key-item">
                        <div class="gi-key-item-label">補助金額</div>
                        <div class="gi-key-item-value amount highlight">
                            <?php echo $amount_display ?: '要確認'; ?>
                        </div>
                        <?php if ($subsidy_rate_display): ?>
                        <div class="gi-key-item-sub">補助率: <?php echo esc_html($subsidy_rate_display); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="gi-key-item">
                        <div class="gi-key-item-label">申請締切</div>
                        <?php if ($days_remaining > 0): ?>
                        <div class="gi-countdown">
                            <span class="gi-countdown-number gi-key-item-value deadline <?php echo $deadline_class; ?>"><?php echo $days_remaining; ?></span>
                            <span class="gi-countdown-unit">日後</span>
                        </div>
                        <div class="gi-key-item-sub"><?php echo esc_html($deadline_info); ?></div>
                        <?php elseif ($deadline_info): ?>
                        <div class="gi-key-item-value deadline <?php echo $deadline_class; ?>">
                            <?php echo esc_html($deadline_info); ?>
                        </div>
                        <?php else: ?>
                        <div class="gi-key-item-value">要確認</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="gi-key-item">
                        <div class="gi-key-item-label">申請難易度</div>
                        <div class="gi-key-item-value"><?php echo $difficulty['label']; ?></div>
                        <div class="gi-key-item-sub">
                            <div class="gi-difficulty-stars" role="img" aria-label="難易度<?php echo $difficulty['stars']; ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="gi-difficulty-star <?php echo $i <= $difficulty['stars'] ? 'active' : ''; ?>" viewBox="0 0 24 24" fill="currentColor">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- AI要約 -->
            <?php if ($grant['ai_summary']): ?>
            <section id="summary" class="gi-card gi-summary-card" aria-labelledby="summary-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    <h2 id="summary-title" class="gi-card-title">AI要約</h2>
                    <span class="gi-summary-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2v20M2 12h20"/>
                        </svg>
                        30秒で理解
                    </span>
                </div>
                <div class="gi-card-body">
                    <p class="gi-summary-text" itemprop="description">
                        <?php echo nl2br(esc_html($grant['ai_summary'])); ?>
                    </p>
                </div>
            </section>
            <?php endif; ?>

            <!-- 詳細情報テーブル -->
            <section id="details" class="gi-card" aria-labelledby="details-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <h2 id="details-title" class="gi-card-title">補助金詳細</h2>
                </div>
                <div class="gi-card-body" style="padding: 0;">
                    <div class="gi-table">
                        <?php if ($grant['organization']): ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">主催機関</div>
                            <div class="gi-table-value">
                                <strong><?php echo esc_html($grant['organization']); ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($amount_display): ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">補助金額</div>
                            <div class="gi-table-value">
                                <span class="gi-value-large gi-value-highlight gi-value-success">
                                    <?php echo esc_html($amount_display); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($subsidy_rate_display): ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">補助率</div>
                            <div class="gi-table-value">
                                <span class="gi-value-highlight"><?php echo esc_html($subsidy_rate_display); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="gi-table-row">
                            <div class="gi-table-key">対象地域</div>
                            <div class="gi-table-value">
                                <?php if ($is_nationwide): ?>
                                <span class="gi-value-highlight" style="font-weight: 700;">全国</span>
                                <?php else: ?>
                                    <?php if (!empty($taxonomies['prefectures'])): ?>
                                    <div style="margin-bottom: var(--space-2);">
                                        <strong style="font-size: var(--text-sm); color: var(--gray-500);">都道府県：</strong><br>
                                        <?php echo $prefecture_display['html']; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($taxonomies['municipalities'])): ?>
                                    <div>
                                        <strong style="font-size: var(--text-sm); color: var(--gray-500);">市町村：</strong><br>
                                        <?php echo $municipality_display['html']; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($deadline_info): ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">申請締切</div>
                            <div class="gi-table-value">
                                <span class="gi-value-highlight <?php echo $days_remaining <= 7 ? 'gi-value-error' : ($days_remaining <= 14 ? 'gi-value-warning' : ''); ?>" style="font-weight: 700;">
                                    <?php echo esc_html($deadline_info); ?>
                                </span>
                                <?php if ($days_remaining > 0): ?>
                                <span style="margin-left: var(--space-2); font-size: var(--text-sm); color: var(--gray-500);">
                                    （残り<?php echo $days_remaining; ?>日）
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($grant['grant_target']): ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">対象者</div>
                            <div class="gi-table-value"><?php echo wp_kses_post($grant['grant_target']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $docs = $grant['required_documents_detailed'] ?: $grant['required_documents'];
                        if ($docs): 
                        ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">必要書類</div>
                            <div class="gi-table-value"><?php echo wp_kses_post($docs); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $expenses = $grant['eligible_expenses_detailed'] ?: $grant['eligible_expenses'];
                        if ($expenses): 
                        ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">対象経費</div>
                            <div class="gi-table-value"><?php echo wp_kses_post($expenses); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="gi-table-row">
                            <div class="gi-table-key">申請難易度</div>
                            <div class="gi-table-value">
                                <div class="gi-difficulty">
                                    <span class="gi-difficulty-label"><?php echo $difficulty['label']; ?></span>
                                    <div class="gi-difficulty-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="gi-difficulty-star <?php echo $i <= $difficulty['stars'] ? 'active' : ''; ?>" viewBox="0 0 24 24" fill="currentColor">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="gi-difficulty-desc"><?php echo $difficulty['desc']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($grant['adoption_rate'] > 0): ?>
                        <div class="gi-table-row">
                            <div class="gi-table-key">採択率</div>
                            <div class="gi-table-value">
                                <span class="gi-value-highlight gi-value-success" style="font-weight: 700;">
                                    <?php echo number_format($grant['adoption_rate'], 1); ?>%
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- 申請チェックリスト -->
            <section id="checklist" class="gi-card gi-checklist-card" aria-labelledby="checklist-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                    <h2 id="checklist-title" class="gi-card-title">申請前チェックリスト</h2>
                </div>
                
                <div class="gi-checklist-progress">
                    <div class="gi-checklist-progress-bar">
                        <div class="gi-checklist-progress-fill" id="checklistProgressFill"></div>
                    </div>
                    <span class="gi-checklist-progress-text" id="checklistProgressText">0 / <?php echo count($checklist_items); ?></span>
                </div>
                
                <ul class="gi-checklist-list" id="checklistList">
                    <?php foreach ($checklist_items as $item): ?>
                    <li class="gi-checklist-item" data-id="<?php echo $item['id']; ?>">
                        <div class="gi-checklist-checkbox" role="checkbox" aria-checked="false" tabindex="0">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <span class="gi-checklist-label">
                            <?php echo esc_html($item['label']); ?>
                            <?php if ($item['required']): ?>
                            <span class="gi-checklist-required">*必須</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="gi-checklist-result">
                    <p class="gi-checklist-result-text" id="checklistResult">
                        チェックを入れて申請可否を確認しましょう
                    </p>
                </div>
            </section>

            <!-- 補助金概要（本文） -->
            <section id="content" class="gi-card" aria-labelledby="content-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <h2 id="content-title" class="gi-card-title">補助金概要</h2>
                </div>
                <div class="gi-card-body">
                    <div class="gi-content" itemprop="articleBody">
                        <?php echo apply_filters('the_content', $content); ?>
                    </div>
                </div>
            </section>

            <!-- 申請の流れ -->
            <?php if ($grant['application_flow']): ?>
            <section id="flow" class="gi-card" aria-labelledby="flow-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <polyline points="19 12 12 19 5 12"/>
                    </svg>
                    <h2 id="flow-title" class="gi-card-title">申請の流れ</h2>
                </div>
                <div class="gi-card-body">
                    <div class="gi-flow">
                        <?php 
                        $flow_steps = explode("\n", $grant['application_flow']);
                        $step_num = 1;
                        foreach ($flow_steps as $step):
                            $step = trim($step);
                            if (empty($step)) continue;
                            $parts = preg_split('/[:：]/', $step, 2);
                            $step_title = trim($parts[0]);
                            $step_desc = isset($parts[1]) ? trim($parts[1]) : '';
                        ?>
                        <div class="gi-flow-step">
                            <div class="gi-flow-number"><?php echo $step_num; ?></div>
                            <div class="gi-flow-content">
                                <h3 class="gi-flow-title"><?php echo esc_html($step_title); ?></h3>
                                <?php if ($step_desc): ?>
                                <p class="gi-flow-desc"><?php echo esc_html($step_desc); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php 
                            $step_num++;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- 申請のコツ -->
            <?php if ($grant['application_tips']): ?>
            <section id="tips" class="gi-card" aria-labelledby="tips-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <h2 id="tips-title" class="gi-card-title">申請のコツ・ポイント</h2>
                </div>
                <div class="gi-card-body">
                    <div class="gi-content">
                        <?php echo wp_kses_post($grant['application_tips']); ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- 採択事例 -->
            <?php if (!empty($grant['success_cases'])): ?>
            <section id="cases" class="gi-card" aria-labelledby="cases-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <h2 id="cases-title" class="gi-card-title">採択事例</h2>
                </div>
                <div class="gi-card-body">
                    <div class="gi-cases-grid">
                        <?php foreach ($grant['success_cases'] as $case): ?>
                        <article class="gi-case-card">
                            <div class="gi-case-header">
                                <div class="gi-case-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                </div>
                                <div class="gi-case-meta">
                                    <?php if (!empty($case['industry'])): ?>
                                    <div class="gi-case-industry"><?php echo esc_html($case['industry']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($case['amount'])): ?>
                                    <div class="gi-case-amount"><?php echo esc_html($case['amount']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($case['purpose'])): ?>
                            <p class="gi-case-purpose"><?php echo esc_html($case['purpose']); ?></p>
                            <?php endif; ?>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
			            <!-- 類似補助金比較 -->
            <?php if (!empty($similar_grants)): ?>
            <section id="compare" class="gi-card" aria-labelledby="compare-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    <h2 id="compare-title" class="gi-card-title">類似補助金との比較</h2>
                </div>
                <div class="gi-card-body" style="overflow-x: auto;">
                    <table class="gi-compare-table">
                        <thead>
                            <tr>
                                <th>項目</th>
                                <th class="gi-compare-current-header">この補助金</th>
                                <?php foreach (array_slice($similar_grants, 0, 3) as $similar): ?>
                                <th>
                                    <a href="<?php echo esc_url($similar['permalink']); ?>" class="gi-compare-link">
                                        <?php echo esc_html(mb_substr($similar['title'], 0, 15, 'UTF-8')); ?>...
                                    </a>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>補助金額</td>
                                <td class="gi-compare-current">
                                    <strong><?php echo $amount_display ?: '要確認'; ?></strong>
                                </td>
                                <?php foreach (array_slice($similar_grants, 0, 3) as $similar): ?>
                                <td><?php echo esc_html($similar['max_amount'] ?: '要確認'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>補助率</td>
                                <td class="gi-compare-current"><?php echo esc_html($subsidy_rate_display ?: '要確認'); ?></td>
                                <?php foreach (array_slice($similar_grants, 0, 3) as $similar): ?>
                                <td><?php echo esc_html($similar['subsidy_rate'] ?: '要確認'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>締切</td>
                                <td class="gi-compare-current"><?php echo esc_html($deadline_info ?: '要確認'); ?></td>
                                <?php foreach (array_slice($similar_grants, 0, 3) as $similar): ?>
                                <td><?php echo esc_html($similar['deadline'] ?: '要確認'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td>難易度</td>
                                <td class="gi-compare-current"><?php echo $difficulty['label']; ?></td>
                                <?php foreach (array_slice($similar_grants, 0, 3) as $similar): 
                                    $sim_diff = $difficulty_map[$similar['difficulty']] ?? $difficulty_map['normal'];
                                ?>
                                <td><?php echo $sim_diff['label']; ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <!-- FAQ -->
            <section id="faq" class="gi-card" aria-labelledby="faq-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <h2 id="faq-title" class="gi-card-title">よくある質問</h2>
                </div>
                <div class="gi-card-body">
                    <div class="gi-faq-list">
                        <?php if ($grant['grant_target']): ?>
                        <details class="gi-faq-item">
                            <summary class="gi-faq-question">
                                <span>この補助金の対象者は誰ですか？</span>
                                <svg class="gi-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </summary>
                            <div class="gi-faq-answer">
                                <?php echo wp_kses_post($grant['grant_target']); ?>
                            </div>
                        </details>
                        <?php endif; ?>
                        
                        <?php 
                        $docs = $grant['required_documents_detailed'] ?: $grant['required_documents'];
                        if ($docs): 
                        ?>
                        <details class="gi-faq-item">
                            <summary class="gi-faq-question">
                                <span>申請に必要な書類は何ですか？</span>
                                <svg class="gi-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </summary>
                            <div class="gi-faq-answer">
                                <?php echo wp_kses_post($docs); ?>
                            </div>
                        </details>
                        <?php endif; ?>
                        
                        <?php 
                        $expenses = $grant['eligible_expenses_detailed'] ?: $grant['eligible_expenses'];
                        if ($expenses): 
                        ?>
                        <details class="gi-faq-item">
                            <summary class="gi-faq-question">
                                <span>どのような経費が対象になりますか？</span>
                                <svg class="gi-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </summary>
                            <div class="gi-faq-answer">
                                <?php echo wp_kses_post($expenses); ?>
                            </div>
                        </details>
                        <?php endif; ?>
                        
                        <details class="gi-faq-item">
                            <summary class="gi-faq-question">
                                <span>申請から採択までどのくらいかかりますか？</span>
                                <svg class="gi-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </summary>
                            <div class="gi-faq-answer">
                                <?php if ($grant['review_period']): ?>
                                <p><?php echo esc_html($grant['review_period']); ?></p>
                                <?php else: ?>
                                <p>通常、申請から採択決定まで1〜2ヶ月程度かかります。補助金の種類や申請時期によって異なる場合がありますので、詳しくは担当窓口にお問い合わせください。</p>
                                <?php endif; ?>
                            </div>
                        </details>
                        
                        <details class="gi-faq-item">
                            <summary class="gi-faq-question">
                                <span>不採択になった場合、再申請は可能ですか？</span>
                                <svg class="gi-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </summary>
                            <div class="gi-faq-answer">
                                <p>多くの場合、次回の募集期間で再申請が可能です。不採択の理由を確認し、改善した上で再度申請することをお勧めします。</p>
                            </div>
                        </details>
                        
                        <details class="gi-faq-item">
                            <summary class="gi-faq-question">
                                <span>専門家に相談した方がいいですか？</span>
                                <svg class="gi-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </summary>
                            <div class="gi-faq-answer">
                                <?php if ($difficulty['level'] >= 4): ?>
                                <p>この補助金は難易度が高いため、中小企業診断士や行政書士など専門家への相談をお勧めします。採択率の向上や書類作成の負担軽減につながります。</p>
                                <?php else: ?>
                                <p>申請書類の作成に不安がある場合や、より採択率を高めたい場合は、専門家への相談を検討してください。商工会議所や中小企業支援センターでも無料相談を受けられる場合があります。</p>
                                <?php endif; ?>
                            </div>
                        </details>
                    </div>
                </div>
            </section>

            <!-- お問い合わせ先 -->
            <?php if ($grant['contact_info'] || $grant['contact_phone'] || $grant['contact_email']): ?>
            <section id="contact" class="gi-card" aria-labelledby="contact-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <h2 id="contact-title" class="gi-card-title">お問い合わせ先</h2>
                </div>
                <div class="gi-card-body">
                    <div style="background: var(--gray-50); padding: var(--space-6); border-radius: var(--radius); border: 1px solid var(--gray-200);">
                        <?php if ($grant['contact_info']): ?>
                        <div style="margin-bottom: var(--space-4);">
                            <?php echo nl2br(esc_html($grant['contact_info'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($grant['contact_phone']): ?>
                        <div style="display: flex; align-items: center; gap: var(--space-2); margin-bottom: var(--space-2);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $grant['contact_phone'])); ?>" style="font-weight: 700; color: var(--primary); text-decoration: none;">
                                <?php echo esc_html($grant['contact_phone']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($grant['contact_email']): ?>
                        <div style="display: flex; align-items: center; gap: var(--space-2);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <a href="mailto:<?php echo esc_attr($grant['contact_email']); ?>" style="color: var(--info); text-decoration: none;">
                                <?php echo esc_html($grant['contact_email']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- 情報ソース・監修者（E-E-A-T対応） -->
            <div class="gi-source-card">
                <div class="gi-source-header">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>情報ソース・更新情報</span>
                </div>
                <div class="gi-source-content">
                    <p>
                        <strong>情報出典：</strong>
                        <?php if ($source_url): ?>
                        <a href="<?php echo esc_url($source_url); ?>" class="gi-source-link" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($source_name ?: '公式サイト'); ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle;">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                        <?php else: ?>
                        <?php echo esc_html($source_name ?: $grant['organization']); ?>
                        <?php endif; ?>
                    </p>
                    <p><strong>最終確認日：</strong><?php echo esc_html($last_verified_display); ?></p>
                    <p style="margin-top: var(--space-3); padding: var(--space-3); background: var(--warning-light); border-radius: var(--radius); font-size: var(--text-sm);">
                        ※ 最新情報は必ず公式サイトでご確認ください。本ページの情報は参考情報であり、内容の正確性を保証するものではありません。
                    </p>
                </div>
            </div>

            <!-- 監修者情報 -->
            <div class="gi-supervisor-card">
                <div class="gi-supervisor-header">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span>監修・編集</span>
                </div>
                <div class="gi-supervisor-content">
                    <div class="gi-supervisor-image">
                        <?php if ($grant['supervisor_image']): ?>
                        <img src="<?php echo esc_url($grant['supervisor_image']['url']); ?>" alt="<?php echo esc_attr($grant['supervisor_name']); ?>">
                        <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 100%; height: 100%; padding: 20px; color: var(--gray-400);">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <div class="gi-supervisor-info">
                        <div class="gi-supervisor-name"><?php echo esc_html($grant['supervisor_name']); ?></div>
                        <div class="gi-supervisor-title"><?php echo esc_html($grant['supervisor_title']); ?></div>
                        <p class="gi-supervisor-profile"><?php echo esc_html($grant['supervisor_profile']); ?></p>
                    </div>
                </div>
            </div>

            <!-- おすすめ補助金セクション -->
            <?php if (!empty($recommended)): ?>
            <aside id="recommended" class="gi-recommend-section" aria-labelledby="recommended-title">
                <div class="gi-recommend-header">
                    <h2 id="recommended-title">あなたにおすすめの補助金</h2>
                    <p>AIが分析した<?php echo count($recommended); ?>件の関連補助金</p>
                </div>
                
                <div class="gi-recommend-grid">
                    <?php foreach ($recommended as $rec): ?>
                    <a href="<?php echo esc_url($rec['permalink']); ?>" class="gi-recommend-card">
                        <div class="gi-recommend-badges">
                            <span class="gi-recommend-badge">募集中</span>
                            <?php if (!empty($rec['match_reasons'])): ?>
                            <?php foreach ($rec['match_reasons'] as $reason): ?>
                            <span class="gi-recommend-badge match"><?php echo esc_html($reason); ?></span>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <h3 class="gi-recommend-title"><?php echo esc_html($rec['title']); ?></h3>
                        <div class="gi-recommend-meta">
                            <?php if ($rec['max_amount']): ?>
                            <div class="gi-recommend-meta-item">
                                <span class="gi-recommend-meta-label">上限額</span>
                                <span class="gi-recommend-meta-value"><?php echo esc_html($rec['max_amount']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($rec['deadline']): ?>
                            <div class="gi-recommend-meta-item">
                                <span class="gi-recommend-meta-label">締切</span>
                                <span class="gi-recommend-meta-value"><?php echo esc_html($rec['deadline']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </aside>
            <?php endif; ?>

            <!-- 関連コラム -->
            <?php if ($related_columns->have_posts()): ?>
            <aside class="gi-card" aria-labelledby="columns-title">
                <div class="gi-card-header">
                    <svg class="gi-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                    <h2 id="columns-title" class="gi-card-title">関連記事・コラム</h2>
                </div>
                <div class="gi-card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: var(--space-4);">
                        <?php while ($related_columns->have_posts()): $related_columns->the_post(); ?>
                        <a href="<?php the_permalink(); ?>" class="gi-recommend-card">
                            <h3 class="gi-recommend-title"><?php the_title(); ?></h3>
                            <div class="gi-recommend-meta">
                                <time datetime="<?php echo get_the_date('c'); ?>">
                                    <?php echo get_the_date('Y年n月j日'); ?>
                                </time>
                            </div>
                        </a>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
            </aside>
            <?php endif; ?>

            <!-- CTA（白文字修正版） -->
            <aside class="gi-cta" aria-labelledby="cta-title">
                <h2 id="cta-title">他にもあなたに合う補助金があるかもしれません</h2>
                <p>AIで最適な補助金を無料診断</p>
                <div class="gi-cta-buttons">
                    <a href="<?php echo home_url('/subsidy-diagnosis/'); ?>" class="gi-btn gi-btn-white gi-btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        無料AI診断を受ける
                    </a>
                    <a href="<?php echo home_url('/grants/'); ?>" class="gi-btn gi-btn-outline-white gi-btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        補助金一覧を見る
                    </a>
                </div>
            </aside>

        </main>

        <!-- サイドバー（PCのみ） -->
        <aside class="gi-sidebar" role="complementary" aria-label="サイドバー">
            
            <!-- PC版AIアシスタント -->
            <div class="gi-sidebar-card gi-ai-sidebar-card">
                <div class="gi-sidebar-header">
                    <h3 class="gi-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        AIアシスタント
                    </h3>
                </div>
                <div class="gi-ai-sidebar-body">
                    <div class="gi-ai-pc-messages" id="pcAiMessages">
                        <div class="gi-ai-pc-message">
                            <div class="gi-ai-pc-avatar">AI</div>
                            <div class="gi-ai-pc-bubble">
                                この補助金について質問があればお気軽にどうぞ！
                            </div>
                        </div>
                    </div>
                    <div class="gi-ai-pc-input-wrapper">
                        <textarea class="gi-ai-pc-input" id="pcAiInput" placeholder="質問を入力..." rows="1"></textarea>
                        <button class="gi-ai-pc-send" id="pcAiSend" type="button" aria-label="送信">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </div>
                    <div class="gi-ai-pc-suggestions">
                        <button class="gi-ai-pc-chip" data-q="申請条件を教えて" type="button">申請条件</button>
                        <button class="gi-ai-pc-chip" data-q="必要書類は？" type="button">必要書類</button>
                        <button class="gi-ai-pc-chip" data-q="対象経費は？" type="button">対象経費</button>
                    </div>
                </div>
            </div>

            <!-- アクションボタン -->
            <div class="gi-sidebar-card">
                <div class="gi-sidebar-body" style="display: flex; flex-direction: column; gap: var(--space-3);">
                    <a href="<?php echo home_url('/subsidy-diagnosis/'); ?>" class="gi-btn gi-btn-accent gi-btn-full">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                        AI無料診断
                    </a>
                    <?php if ($grant['official_url']): ?>
                    <a href="<?php echo esc_url($grant['official_url']); ?>" class="gi-btn gi-btn-primary gi-btn-full" target="_blank" rel="noopener noreferrer">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                        公式サイト
                    </a>
                    <?php endif; ?>
                    <button class="gi-btn gi-btn-secondary gi-btn-full" id="bookmarkBtn" type="button">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                        </svg>
                        保存する
                    </button>
                </div>
            </div>

            <!-- 目次 -->
            <nav class="gi-sidebar-card" aria-labelledby="sidebar-toc-title">
                <div class="gi-sidebar-header">
                    <h3 id="sidebar-toc-title" class="gi-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                        目次
                    </h3>
                </div>
                <ul class="gi-toc-list">
                    <?php foreach ($toc_items as $item): ?>
                    <li class="gi-toc-item">
                        <a href="#<?php echo esc_attr($item['id']); ?>" class="gi-toc-link">
                            <?php echo esc_html($item['title']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- 締切間近の補助金 -->
            <?php if ($grant_deadline_soon->have_posts()): ?>
            <section class="gi-sidebar-card" aria-labelledby="sidebar-deadline-title">
                <div class="gi-sidebar-header">
                    <h3 id="sidebar-deadline-title" class="gi-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        締切間近
                    </h3>
                </div>
                <ul class="gi-sidebar-list">
                    <?php while ($grant_deadline_soon->have_posts()): $grant_deadline_soon->the_post(); 
                        $item_deadline = get_field('deadline_date');
                        $item_days = $item_deadline ? ceil((strtotime($item_deadline) - current_time('timestamp')) / 86400) : 0;
                    ?>
                    <li class="gi-sidebar-list-item">
                        <a href="<?php the_permalink(); ?>" class="gi-sidebar-list-link">
                            <span class="gi-sidebar-list-rank" style="background: <?php echo $item_days <= 7 ? 'var(--error)' : 'var(--warning)'; ?>; color: var(--white);">
                                <?php echo $item_days; ?>日
                            </span>
                            <div class="gi-sidebar-list-content">
                                <div class="gi-sidebar-list-title"><?php the_title(); ?></div>
                                <div class="gi-sidebar-list-meta">
                                    <?php echo date('n/j', strtotime($item_deadline)); ?>締切
                                </div>
                            </div>
                        </a>
                    </li>
                    <?php endwhile; wp_reset_postdata(); ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- 人気の補助金 -->
            <?php if ($grant_ranking->have_posts()): ?>
            <section class="gi-sidebar-card" aria-labelledby="sidebar-ranking-title">
                <div class="gi-sidebar-header">
                    <h3 id="sidebar-ranking-title" class="gi-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                        人気ランキング
                    </h3>
                </div>
                <ul class="gi-sidebar-list">
                    <?php 
                    $rank = 1;
                    while ($grant_ranking->have_posts()): $grant_ranking->the_post(); 
                        $rank_class = '';
                        if ($rank === 1) $rank_class = 'gold';
                        elseif ($rank === 2) $rank_class = 'silver';
                        elseif ($rank === 3) $rank_class = 'bronze';
                    ?>
                    <li class="gi-sidebar-list-item">
                        <a href="<?php the_permalink(); ?>" class="gi-sidebar-list-link">
                            <span class="gi-sidebar-list-rank <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                            <div class="gi-sidebar-list-content">
                                <div class="gi-sidebar-list-title"><?php the_title(); ?></div>
                                <div class="gi-sidebar-list-meta">
                                    <?php echo number_format(get_field('views_count')); ?>回閲覧
                                </div>
                            </div>
                        </a>
                    </li>
                    <?php 
                        $rank++;
                    endwhile; 
                    wp_reset_postdata(); 
                    ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- 関連カテゴリ -->
            <?php if (!empty($taxonomies['categories'])): ?>
            <nav class="gi-sidebar-card" aria-labelledby="sidebar-category-title">
                <div class="gi-sidebar-header">
                    <h3 id="sidebar-category-title" class="gi-sidebar-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                        </svg>
                        関連カテゴリ
                    </h3>
                </div>
                <div class="gi-sidebar-body">
                    <?php foreach ($taxonomies['categories'] as $cat): ?>
                    <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="gi-tag" style="display: block; margin-bottom: var(--space-2);">
                        <?php echo esc_html($cat->name); ?>の補助金一覧
                    </a>
                    <?php endforeach; ?>
                </div>
            </nav>
            <?php endif; ?>

        </aside>
    </div>
</div>



<!-- モバイルAIボタン -->
<button class="gi-mobile-ai-btn" id="mobileAiBtn" type="button" aria-label="AIアシスタントを開く">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
    </svg>
    <span class="gi-mobile-ai-btn-text">AI相談</span>
</button>

<!-- モバイルオーバーレイ -->
<div class="gi-mobile-overlay" id="mobileOverlay" aria-hidden="true"></div>

<!-- モバイルパネル -->
<div class="gi-mobile-panel" id="mobilePanel" role="dialog" aria-labelledby="mobilePanelTitle" aria-modal="true">
    <div class="gi-mobile-panel-handle"></div>
    <div class="gi-mobile-panel-header">
        <h2 id="mobilePanelTitle" class="gi-mobile-panel-title">AIアシスタント</h2>
        <button class="gi-mobile-panel-close" id="mobilePanelClose" type="button" aria-label="閉じる">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    
    <div class="gi-mobile-tabs" role="tablist">
        <button class="gi-mobile-tab active" data-tab="ai" type="button" role="tab" aria-selected="true">AI質問</button>
        <button class="gi-mobile-tab" data-tab="toc" type="button" role="tab" aria-selected="false">目次</button>
        <button class="gi-mobile-tab" data-tab="action" type="button" role="tab" aria-selected="false">アクション</button>
    </div>
    
    <!-- AIタブ -->
    <div class="gi-mobile-content active" id="mobileAiContent" role="tabpanel">
        <div class="gi-ai-messages" id="mobileAiMessages" aria-live="polite">
            <div class="gi-ai-message">
                <div class="gi-ai-avatar">AI</div>
                <div class="gi-ai-bubble">
                    こんにちは！「<?php echo esc_js(get_the_title()); ?>」について何でもお聞きください。
                </div>
            </div>
        </div>
        <div class="gi-ai-input-wrapper">
            <textarea class="gi-ai-input" id="mobileAiInput" placeholder="質問を入力..." rows="1" aria-label="質問を入力"></textarea>
            <button class="gi-ai-send" id="mobileAiSend" type="button" aria-label="送信">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>
        <div class="gi-ai-suggestions">
            <button class="gi-ai-chip" data-q="申請条件を教えて" type="button">申請条件は？</button>
            <button class="gi-ai-chip" data-q="必要書類は何？" type="button">必要書類は？</button>
            <button class="gi-ai-chip" data-q="対象経費は？" type="button">対象経費は？</button>
            <button class="gi-ai-chip" data-q="申請のコツは？" type="button">申請のコツ</button>
        </div>
    </div>
    
    <!-- 目次タブ -->
    <div class="gi-mobile-content" id="mobileTocContent" role="tabpanel">
        <ul class="gi-toc-list">
            <?php foreach ($toc_items as $item): ?>
            <li class="gi-toc-item">
                <a href="#<?php echo esc_attr($item['id']); ?>" class="gi-toc-link mobile-toc-link">
                    <?php echo esc_html($item['title']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <!-- アクションタブ -->
    <div class="gi-mobile-content" id="mobileActionContent" role="tabpanel">
        <div style="display: flex; flex-direction: column; gap: var(--space-3);">
            <a href="<?php echo home_url('/subsidy-diagnosis/'); ?>" class="gi-btn gi-btn-accent gi-btn-full gi-btn-lg">
                AI無料診断
            </a>
            <?php if ($grant['official_url']): ?>
            <a href="<?php echo esc_url($grant['official_url']); ?>" class="gi-btn gi-btn-primary gi-btn-full" target="_blank" rel="noopener noreferrer">
                公式サイトを見る
            </a>
            <?php endif; ?>
            <button class="gi-btn gi-btn-secondary gi-btn-full" id="mobileBookmarkBtn" type="button">
                この補助金を保存する
            </button>
            <button class="gi-btn gi-btn-secondary gi-btn-full" id="mobileShareBtn" type="button">
                シェアする
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // ===================================
    // 読了進捗バー
    // ===================================
    const progressBar = document.getElementById('progressBar');
    
    function updateProgress() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollPercent = Math.min(100, (scrollTop / (documentHeight - windowHeight)) * 100);
        
        if (progressBar) {
            progressBar.style.width = scrollPercent + '%';
            progressBar.setAttribute('aria-valuenow', Math.round(scrollPercent));
        }
    }
    
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
    
    // ===================================
    // チェックリスト
    // ===================================
    const checklistItems = document.querySelectorAll('.gi-checklist-item');
    const progressFill = document.getElementById('checklistProgressFill');
    const progressText = document.getElementById('checklistProgressText');
    const resultText = document.getElementById('checklistResult');
    const totalItems = checklistItems.length;
    
    function updateChecklist() {
        const checkedItems = document.querySelectorAll('.gi-checklist-item.checked').length;
        const percent = (checkedItems / totalItems) * 100;
        
        if (progressFill) progressFill.style.width = percent + '%';
        if (progressText) progressText.textContent = checkedItems + ' / ' + totalItems;
        
        if (resultText) {
            if (checkedItems === totalItems) {
                resultText.textContent = '✓ すべての条件をクリア！申請可能です';
                resultText.classList.add('complete');
            } else if (checkedItems > 0) {
                resultText.textContent = 'あと' + (totalItems - checkedItems) + '項目で申請可能です';
                resultText.classList.remove('complete');
            } else {
                resultText.textContent = 'チェックを入れて申請可否を確認しましょう';
                resultText.classList.remove('complete');
            }
        }
        
        const checkedIds = [];
        document.querySelectorAll('.gi-checklist-item.checked').forEach(function(item) {
            checkedIds.push(item.dataset.id);
        });
        localStorage.setItem('gi_checklist_<?php echo $post_id; ?>', JSON.stringify(checkedIds));
    }
    
    const savedChecks = localStorage.getItem('gi_checklist_<?php echo $post_id; ?>');
    if (savedChecks) {
        try {
            const checkedIds = JSON.parse(savedChecks);
            checklistItems.forEach(function(item) {
                if (checkedIds.includes(item.dataset.id)) {
                    item.classList.add('checked');
                    const checkbox = item.querySelector('.gi-checklist-checkbox');
                    if (checkbox) checkbox.setAttribute('aria-checked', 'true');
                }
            });
            updateChecklist();
        } catch (e) {}
    }
    
    checklistItems.forEach(function(item) {
        const checkbox = item.querySelector('.gi-checklist-checkbox');
        
        function toggleCheck() {
            item.classList.toggle('checked');
            const isChecked = item.classList.contains('checked');
            if (checkbox) checkbox.setAttribute('aria-checked', isChecked ? 'true' : 'false');
            updateChecklist();
        }
        
        item.addEventListener('click', toggleCheck);
        
        if (checkbox) {
            checkbox.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleCheck();
                }
            });
        }
    });
    
    // ===================================
    // 目次スムーススクロール＆アクティブ状態
    // ===================================
    const tocLinks = document.querySelectorAll('.gi-toc-link');
    const sections = document.querySelectorAll('section[id], aside[id]');
    
    tocLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const headerOffset = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                if (link.classList.contains('mobile-toc-link')) {
                    closeMobilePanel();
                }
            }
        });
    });
    
    const observerOptions = {
        root: null,
        rootMargin: '-20% 0px -60% 0px',
        threshold: 0
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                tocLinks.forEach(function(link) {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + id) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }, observerOptions);
    
    sections.forEach(function(section) {
        observer.observe(section);
    });
    
    // ===================================
    // FAQ アコーディオン
    // ===================================
    const faqItems = document.querySelectorAll('.gi-faq-item');
    
    faqItems.forEach(function(item) {
        item.addEventListener('toggle', function() {
            const summary = item.querySelector('.gi-faq-question');
            if (summary) {
                summary.setAttribute('aria-expanded', item.open ? 'true' : 'false');
            }
        });
    });
    
    // ===================================
    // ブックマーク機能
    // ===================================
    const bookmarkBtn = document.getElementById('bookmarkBtn');
    const mobileBookmarkBtn = document.getElementById('mobileBookmarkBtn');
    const postId = <?php echo $post_id; ?>;
    
    function getBookmarks() {
        const saved = localStorage.getItem('gi_bookmarks');
        return saved ? JSON.parse(saved) : [];
    }
    
    function isBookmarked() {
        return getBookmarks().includes(postId);
    }
    
    function updateBookmarkUI() {
        const bookmarked = isBookmarked();
        const text = bookmarked ? '保存済み' : '保存する';
        
        if (bookmarkBtn) {
            bookmarkBtn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="${bookmarked ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                </svg>
                ${text}
            `;
        }
        
        if (mobileBookmarkBtn) {
            mobileBookmarkBtn.textContent = bookmarked ? '保存済み ✓' : 'この補助金を保存する';
        }
    }
    
    function toggleBookmark() {
        let bookmarks = getBookmarks();
        
        if (isBookmarked()) {
            bookmarks = bookmarks.filter(function(id) { return id !== postId; });
        } else {
            bookmarks.push(postId);
        }
        
        localStorage.setItem('gi_bookmarks', JSON.stringify(bookmarks));
        updateBookmarkUI();
    }
    
    if (bookmarkBtn) bookmarkBtn.addEventListener('click', toggleBookmark);
    if (mobileBookmarkBtn) mobileBookmarkBtn.addEventListener('click', toggleBookmark);
    updateBookmarkUI();
    
    // ===================================
    // シェア機能
    // ===================================
    const mobileShareBtn = document.getElementById('mobileShareBtn');
    
    if (mobileShareBtn) {
        mobileShareBtn.addEventListener('click', function() {
            const shareData = {
                title: '<?php echo esc_js(get_the_title()); ?>',
                text: '<?php echo esc_js($meta_desc); ?>',
                url: '<?php echo esc_js($canonical_url); ?>'
            };
            
            if (navigator.share) {
                navigator.share(shareData).catch(function() {});
            } else {
                navigator.clipboard.writeText(shareData.url).then(function() {
                    alert('URLをコピーしました');
                }).catch(function() {});
            }
        });
    }
    
    // ===================================
    // モバイルパネル
    // ===================================
    const mobileAiBtn = document.getElementById('mobileAiBtn');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobilePanel = document.getElementById('mobilePanel');
    const mobilePanelClose = document.getElementById('mobilePanelClose');
    const mobileTabs = document.querySelectorAll('.gi-mobile-tab');
    const mobileContents = document.querySelectorAll('.gi-mobile-content');
    
    function openMobilePanel() {
        if (mobileOverlay) {
            mobileOverlay.classList.add('active');
            mobileOverlay.setAttribute('aria-hidden', 'false');
        }
        if (mobilePanel) mobilePanel.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobilePanel() {
        if (mobileOverlay) {
            mobileOverlay.classList.remove('active');
            mobileOverlay.setAttribute('aria-hidden', 'true');
        }
        if (mobilePanel) mobilePanel.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (mobileAiBtn) mobileAiBtn.addEventListener('click', openMobilePanel);
    if (mobilePanelClose) mobilePanelClose.addEventListener('click', closeMobilePanel);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobilePanel);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobilePanel && mobilePanel.classList.contains('active')) {
            closeMobilePanel();
        }
    });
    
    mobileTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            mobileTabs.forEach(function(t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            mobileContents.forEach(function(c) {
                c.classList.remove('active');
            });
            
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            const targetContent = document.getElementById('mobile' + targetTab.charAt(0).toUpperCase() + targetTab.slice(1) + 'Content');
            if (targetContent) targetContent.classList.add('active');
        });
    });
    
    // ===================================
    // AIチャット共通関数
    // ===================================
    async function sendAiQuestion(input, messagesContainer, sendBtn, type) {
        const question = input.value.trim();
        if (!question) return;
        
        addMessage(messagesContainer, question, 'user', type);
        input.value = '';
        if (type === 'mobile') input.style.height = 'auto';
        sendBtn.disabled = true;
        
        const typingId = showTyping(messagesContainer, type);
        
        try {
            const formData = new FormData();
            formData.append('action', 'handle_grant_ai_question');
            formData.append('nonce', '<?php echo wp_create_nonce("gi_ajax_nonce"); ?>');
            formData.append('post_id', '<?php echo $post_id; ?>');
            formData.append('question', question);
            
            const response = await fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            removeTyping(typingId);
            
            if (data.success && data.data && data.data.answer) {
                addMessage(messagesContainer, data.data.answer, 'assistant', type);
            } else {
                addMessage(messagesContainer, '申し訳ございません。回答の生成に失敗しました。ページ内の情報をご確認いただくか、公式サイトにお問い合わせください。', 'assistant', type);
            }
        } catch (error) {
            removeTyping(typingId);
            addMessage(messagesContainer, '通信エラーが発生しました。しばらく経ってから再度お試しください。', 'assistant', type);
        } finally {
            sendBtn.disabled = false;
        }
    }
    
    function addMessage(container, content, msgType, uiType) {
        const msg = document.createElement('div');
        
        if (uiType === 'pc') {
            msg.className = 'gi-ai-pc-message ' + msgType;
            msg.innerHTML = `
                <div class="gi-ai-pc-avatar">${msgType === 'user' ? 'U' : 'AI'}</div>
                <div class="gi-ai-pc-bubble">${content.replace(/\n/g, '<br>')}</div>
            `;
        } else {
            msg.className = 'gi-ai-message ' + msgType;
            msg.innerHTML = `
                <div class="gi-ai-avatar">${msgType === 'user' ? 'U' : 'AI'}</div>
                <div class="gi-ai-bubble">${content.replace(/\n/g, '<br>')}</div>
            `;
        }
        
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }
    
    function showTyping(container, uiType) {
        const typing = document.createElement('div');
        const typingId = 'typing_' + Date.now();
        typing.id = typingId;
        
        if (uiType === 'pc') {
            typing.className = 'gi-ai-pc-message';
            typing.innerHTML = `
                <div class="gi-ai-pc-avatar">AI</div>
                <div class="gi-ai-pc-bubble">入力中...</div>
            `;
        } else {
            typing.className = 'gi-ai-message';
            typing.innerHTML = `
                <div class="gi-ai-avatar">AI</div>
                <div class="gi-ai-bubble">入力中...</div>
            `;
        }
        
        container.appendChild(typing);
        container.scrollTop = container.scrollHeight;
        return typingId;
    }
    
    function removeTyping(id) {
        const typing = document.getElementById(id);
        if (typing) typing.remove();
    }
    
    // ===================================
    // モバイルAIチャット
    // ===================================
    const mobileAiInput = document.getElementById('mobileAiInput');
    const mobileAiSend = document.getElementById('mobileAiSend');
    const mobileAiMessages = document.getElementById('mobileAiMessages');
    const mobileAiChips = document.querySelectorAll('.gi-mobile-panel .gi-ai-chip');
    
    if (mobileAiInput) {
        mobileAiInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
    }
    
    if (mobileAiSend && mobileAiInput && mobileAiMessages) {
        mobileAiSend.addEventListener('click', function() {
            sendAiQuestion(mobileAiInput, mobileAiMessages, mobileAiSend, 'mobile');
        });
        
        mobileAiInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendAiQuestion(mobileAiInput, mobileAiMessages, mobileAiSend, 'mobile');
            }
        });
    }
    
    mobileAiChips.forEach(function(chip) {
        chip.addEventListener('click', function() {
            if (mobileAiInput) {
                mobileAiInput.value = this.dataset.q;
                sendAiQuestion(mobileAiInput, mobileAiMessages, mobileAiSend, 'mobile');
            }
        });
    });
    
    // ===================================
    // PC版AIチャット
    // ===================================
    const pcAiInput = document.getElementById('pcAiInput');
    const pcAiSend = document.getElementById('pcAiSend');
    const pcAiMessages = document.getElementById('pcAiMessages');
    const pcAiChips = document.querySelectorAll('.gi-ai-pc-chip');
    
    if (pcAiInput) {
        pcAiInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 80) + 'px';
        });
    }
    
    if (pcAiSend && pcAiInput && pcAiMessages) {
        pcAiSend.addEventListener('click', function() {
            sendAiQuestion(pcAiInput, pcAiMessages, pcAiSend, 'pc');
        });
        
        pcAiInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendAiQuestion(pcAiInput, pcAiMessages, pcAiSend, 'pc');
            }
        });
    }
    
    pcAiChips.forEach(function(chip) {
        chip.addEventListener('click', function() {
            if (pcAiInput) {
                pcAiInput.value = this.dataset.q;
                sendAiQuestion(pcAiInput, pcAiMessages, pcAiSend, 'pc');
            }
        });
    });
    
    // ===================================
    // スムーススクロール（ページ内リンク全般）
    // ===================================
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
});
</script>

<?php get_footer(); ?>
