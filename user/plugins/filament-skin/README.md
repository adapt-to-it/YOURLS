# Filament Skin per YOURLS

Redesign completo della dashboard admin di YOURLS con uno stile ispirato a [Filament](https://filamentphp.com): sidebar laterale persistente, layout full-page, design system con token CSS, palette Indigo + Slate, dark mode con toggle.

## Cosa cambia

- Sidebar a sinistra con voci Dashboard / Tools / Plugins / Logout (256px, sticky)
- Topbar con titolo pagina, theme toggle, link aiuto
- Card design per form, modali, tabella link
- Action button sempre visibili (non solo on hover)
- Modal `<dialog>` ridisegnato con backdrop blur
- Login screen centrato come card
- Mobile responsive: sidebar diventa drawer con hamburger
- Dark mode automatico (prefers-color-scheme) + toggle manuale persistente

**Cosa NON tocca:** la frontend pubblica, la redirezione degli shortlink, l'API, la logica di YOURLS. Solo CSS/HTML wrapper della dashboard.

## Installazione

1. Carica la cartella `filament-skin/` in `user/plugins/` del tuo YOURLS.
2. Vai su `/admin/plugins.php` e clicca **Activate** su Filament Skin.
3. Applica le 2 patch al core (vedi sotto).
4. Ricarica `/admin/`.

## Patch al core (necessarie)

Senza queste patch alcuni elementi del DOM nativo creano conflitti con il layout. Sono modifiche minime, di 1 riga.

**File:** `includes/functions-html.php`

### Patch 1 — riga 11 (dentro `yourls_html_logo()`)

Trova:

```php
yourls_do_action( 'pre_html_logo' );
?>
<header role="banner">
```

Sostituisci con:

```php
yourls_do_action( 'pre_html_logo' );
yourls_do_action( 'shell_before_logo' );
?>
<header role="banner">
```

### Patch 2 — riga 189 (dentro `yourls_html_addnew()`)

Trova:

```php
?>
<main role="main">
<div id="new_url">
```

Sostituisci con:

```php
?>
<section role="region" class="yourls-addnew">
<div id="new_url">
```

E poco sotto, nello stesso file, trova la chiusura `</main>` corrispondente e sostituiscila con `</section>`.

> **Nota:** queste patch vanno **riapplicate dopo ogni upgrade di YOURLS**. Sono retrocompatibili: il plugin disattivato torna l'admin originale.

## Disattivazione

Vai su `/admin/plugins.php` → **Deactivate** su Filament Skin. La dashboard torna esattamente all'aspetto originale (le patch al core non hanno effetti visibili senza il plugin attivo).

## Personalizzazione

Tutti i colori e le dimensioni sono **CSS variables** in `assets/filament-skin.css` (blocco `:root`). Modifica `--fs-color-primary-*` per cambiare l'accento, `--fs-sidebar-w` per la larghezza sidebar, ecc.

Per cambiare le voci di menu della sidebar, hooka il filtro PHP:

```php
yourls_add_filter('fs_skin_nav_links', function($links) {
    $links['custom'] = array(
        'url' => yourls_admin_url('plugins.php?page=mio-plugin'),
        'anchor' => 'Mia voce',
        'icon' => 'tools',
    );
    return $links;
});
```

## Compatibilità

- YOURLS 1.10.x
- PHP 8.0+
- Browser moderni (Chrome/Edge 90+, Firefox 90+, Safari 14+) — usa `<dialog>` HTML5, CSS Grid, CSS Variables.

## Versione

1.0.0
