<?php
/*
Plugin Name: Filament Skin
Plugin URI: https://github.com/local/filament-skin
Description: Modernizes the YOURLS admin with a Filament-inspired sidebar layout, design system tokens, dark mode and standardized components. Affects only the admin dashboard.
Version: 1.0.0
Author: Custom
Author URI: https://link.forzavitale.it
*/

if ( !defined( 'YOURLS_ABSPATH' ) ) die();

define( 'FS_SKIN_VERSION', '1.0.0' );
define( 'FS_SKIN_URL', yourls_site_url( false ) . '/user/plugins/filament-skin' );

yourls_add_action( 'html_head',       'fs_skin_enqueue_assets' );
yourls_add_action( 'pre_html_logo',   'fs_skin_open_shell' );
yourls_add_action( 'html_footer',     'fs_skin_close_shell' );
yourls_add_filter( 'bodyclass',       'fs_skin_body_class' );
yourls_add_filter( 'logout_link',     'fs_skin_logout_link', 1 );

function fs_skin_should_run( $context = null ) {
    if ( $context === null ) {
        $context = function_exists( 'yourls_get_html_context' ) ? yourls_get_html_context() : 'index';
    }
    return !in_array( $context, array( 'install', 'upgrade', 'new' ), true );
}

function fs_skin_enqueue_assets( $context = '' ) {
    if ( !fs_skin_should_run( $context ) ) return;
    $v = FS_SKIN_VERSION;
    $u = FS_SKIN_URL;
    echo "\n<link rel=\"stylesheet\" href=\"$u/assets/filament-skin.css?v=$v\" type=\"text/css\" media=\"screen\" />\n";
    echo "<script src=\"$u/assets/filament-skin.js?v=$v\" defer></script>\n";
    echo "<script>(function(){try{var t=localStorage.getItem('fs-theme');if(t){document.documentElement.setAttribute('data-fs-theme',t);}}catch(e){}})();</script>\n";
}

function fs_skin_body_class( $class ) {
    if ( !fs_skin_should_run() ) return $class;
    return trim( $class . ' fs-skin' );
}

function fs_skin_logout_link( $link ) {
    return $link;
}

/**
 * Apre il guscio: sidebar + main wrapper + topbar.
 * Hook su pre_html_logo che spara da functions-html.php:9.
 */
function fs_skin_open_shell() {
    static $opened = false;
    if ( $opened || !fs_skin_should_run() ) return;

    $context = yourls_get_html_context();
    if ( $context === 'login' ) return;

    $opened = true;
    $GLOBALS['fs_skin_shell_opened'] = true;

    $links = fs_skin_get_nav_links();
    $current = fs_skin_current_nav_key( $context );
    $page_title = fs_skin_page_title( $context );
    $user = defined( 'YOURLS_USER' ) ? YOURLS_USER : '';
    $logout_url = '';
    if ( defined( 'YOURLS_USER' ) ) {
        $logout_url = yourls_nonce_url( 'admin_logout',
            yourls_add_query_arg( array( 'action' => 'logout' ), yourls_admin_url( 'index.php' ) ),
            'nonce', 'logout' );
    }
    ?>
    <div class="fs-shell" data-fs-shell>
        <aside class="fs-sidebar" data-fs-sidebar>
            <div class="fs-sidebar__brand">
                <div class="fs-sidebar__brand-mark" aria-hidden="true"><?php echo fs_skin_icon('link'); ?></div>
                <div class="fs-sidebar__brand-text">
                    <strong>YOURLS</strong>
                    <span><?php echo htmlspecialchars( parse_url( YOURLS_SITE, PHP_URL_HOST ) ?: 'admin' ); ?></span>
                </div>
                <button class="fs-sidebar__close" type="button" aria-label="Close menu" data-fs-sidebar-close>&times;</button>
            </div>
            <nav class="fs-sidebar__nav" aria-label="Main">
                <?php foreach ( $links as $key => $l ): ?>
                    <a href="<?php echo $l['url']; ?>"
                       class="fs-sidebar__nav-item<?php echo $current === $key ? ' is-active' : ''; ?>"
                       <?php echo !empty($l['title']) ? 'title="'.htmlspecialchars($l['title']).'"' : ''; ?>>
                        <span class="fs-sidebar__nav-icon"><?php echo fs_skin_icon( $l['icon'] ); ?></span>
                        <span class="fs-sidebar__nav-label"><?php echo htmlspecialchars( $l['anchor'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="fs-sidebar__footer">
                <?php if ( $user ): ?>
                    <div class="fs-sidebar__user">
                        <div class="fs-sidebar__avatar"><?php echo strtoupper( substr( $user, 0, 1 ) ); ?></div>
                        <div class="fs-sidebar__user-meta">
                            <strong><?php echo htmlspecialchars( $user ); ?></strong>
                            <span>signed in</span>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ( $logout_url ): ?>
                    <a href="<?php echo $logout_url; ?>" class="fs-sidebar__nav-item fs-sidebar__nav-item--muted">
                        <span class="fs-sidebar__nav-icon"><?php echo fs_skin_icon('logout'); ?></span>
                        <span class="fs-sidebar__nav-label">Logout</span>
                    </a>
                <?php endif; ?>
            </div>
        </aside>

        <div class="fs-sidebar__backdrop" data-fs-sidebar-backdrop></div>

        <main class="fs-main">
            <header class="fs-topbar">
                <button class="fs-topbar__hamburger" type="button" aria-label="Open menu" data-fs-sidebar-open><?php echo fs_skin_icon('menu'); ?></button>
                <div class="fs-topbar__title">
                    <h1><?php echo htmlspecialchars( $page_title ); ?></h1>
                </div>
                <div class="fs-topbar__actions">
                    <button type="button" class="fs-icon-btn" data-fs-theme-toggle aria-label="Toggle theme">
                        <span data-fs-theme-icon="light"><?php echo fs_skin_icon('sun'); ?></span>
                        <span data-fs-theme-icon="dark"><?php echo fs_skin_icon('moon'); ?></span>
                    </button>
                    <a href="<?php echo yourls_site_url(false) . '/readme.html'; ?>" class="fs-icon-btn" aria-label="Help" title="Help"><?php echo fs_skin_icon('help'); ?></a>
                </div>
            </header>
            <div class="fs-page">
    <?php
}

function fs_skin_close_shell() {
    if ( empty( $GLOBALS['fs_skin_shell_opened'] ) ) return;
    echo "\n</div></main></div><!-- /fs-shell -->\n";
}

function fs_skin_get_nav_links() {
    $links = array(
        'admin' => array(
            'url'    => yourls_admin_url( 'index.php' ),
            'anchor' => 'Dashboard',
            'title'  => 'Manage your short URLs',
            'icon'   => 'dashboard',
        ),
        'tools' => array(
            'url'    => yourls_admin_url( 'tools.php' ),
            'anchor' => 'Tools',
            'title'  => 'Bookmarklets and API tools',
            'icon'   => 'tools',
        ),
        'plugins' => array(
            'url'    => yourls_admin_url( 'plugins.php' ),
            'anchor' => 'Plugins',
            'title'  => 'Manage plugins',
            'icon'   => 'plugins',
        ),
    );
    return yourls_apply_filter( 'fs_skin_nav_links', $links );
}

function fs_skin_current_nav_key( $context ) {
    switch ( $context ) {
        case 'index':   return 'admin';
        case 'tools':   return 'tools';
        case 'plugins': return 'plugins';
        default:        return null;
    }
}

function fs_skin_page_title( $context ) {
    switch ( $context ) {
        case 'index':   return 'Dashboard';
        case 'tools':   return 'Tools';
        case 'plugins': return 'Plugins';
        case 'login':   return 'Sign in';
        default:        return 'Admin';
    }
}

function fs_skin_icon( $name ) {
    static $icons = null;
    if ( $icons === null ) {
        $icons = array(
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
            'tools'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0 5 5l-9.4 9.4a2.1 2.1 0 1 1-3-3l9.4-9.4z"/><path d="M5 5l4 4"/></svg>',
            'plugins'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v4H5a2 2 0 0 0-2 2v4h4a2 2 0 1 1 0 4H3v4a2 2 0 0 0 2 2h4v-4a2 2 0 1 1 4 0v4h4a2 2 0 0 0 2-2v-4h-4a2 2 0 1 1 0-4h4V9a2 2 0 0 0-2-2h-4V3"/></svg>',
            'logout'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
            'menu'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
            'link'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>',
            'sun'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>',
            'moon'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>',
            'help'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4"/><line x1="12" y1="17" x2="12" y2="17.5"/></svg>',
        );
    }
    if ( !isset( $icons[$name] ) ) return '';
    return str_replace( '<svg ', '<svg class="fs-icon" width="20" height="20" ', $icons[$name] );
}
