<?php
/**
 * Showcase UI helpers: 3D electric cards.
 */

/** SVG turbulence filters that make the card borders "crackle". Output once per page. */
function electric_filters(): void
{
    $defs = [[1, 700, 490, 6, 1], [2, 500, 350, 8, 3], [3, 600, 420, 7, 5]];
    echo '<svg class="ecard-svg" aria-hidden="true" focusable="false"><defs>';
    foreach ($defs as [$id, $dy, $dx, $dur, $seed]) {
        $s2 = $seed + 1;
        echo <<<SVG
<filter id="cp-turb-{$id}" color-interpolation-filters="sRGB" x="-20%" y="-20%" width="140%" height="140%">
  <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="4" result="n1" seed="{$seed}"/>
  <feOffset in="n1" dx="0" dy="0" result="o1"><animate attributeName="dy" values="{$dy}; 0" dur="{$dur}s" repeatCount="indefinite" calcMode="linear"/></feOffset>
  <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="4" result="n2" seed="{$seed}"/>
  <feOffset in="n2" dx="0" dy="0" result="o2"><animate attributeName="dy" values="0; -{$dy}" dur="{$dur}s" repeatCount="indefinite" calcMode="linear"/></feOffset>
  <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="4" result="n3" seed="{$s2}"/>
  <feOffset in="n3" dx="0" dy="0" result="o3"><animate attributeName="dx" values="{$dx}; 0" dur="{$dur}s" repeatCount="indefinite" calcMode="linear"/></feOffset>
  <feTurbulence type="turbulence" baseFrequency="0.02" numOctaves="4" result="n4" seed="{$s2}"/>
  <feOffset in="n4" dx="0" dy="0" result="o4"><animate attributeName="dx" values="0; -{$dx}" dur="{$dur}s" repeatCount="indefinite" calcMode="linear"/></feOffset>
  <feComposite in="o1" in2="o2" result="p1"/>
  <feComposite in="o3" in2="o4" result="p2"/>
  <feBlend in="p1" in2="p2" mode="color-dodge" result="combined"/>
  <feDisplacementMap in="SourceGraphic" in2="combined" scale="30" xChannelSelector="R" yChannelSelector="B"/>
</filter>
SVG;
    }
    echo '</defs></svg>';
}

/**
 * One electric card.
 * $n: 1-3 (selects filter), $hex/$rgb: accent colour, $link: [label, href] or null
 */
function electric_card(int $n, string $hex, string $rgb, string $badge, string $icon, string $title, string $desc, ?array $link = null): void
{
    ?>
    <article class="ecard" style="--e: <?= e($hex) ?>; --e-rgb: <?= e($rgb) ?>;">
        <div class="ecard-inner">
            <div class="ecard-border"><div class="ecard-main" style="filter: url(#cp-turb-<?= $n ?>);"></div></div>
            <div class="ecard-glow1"></div>
            <div class="ecard-glow2"></div>
        </div>
        <div class="ecard-overlay"></div>
        <div class="ecard-bg-glow"></div>
        <div class="ecard-content">
            <div class="ecard-head">
                <div class="ecard-top">
                    <span class="glass-badge"><?= e($badge) ?></span>
                    <span class="ecard-icon"><i class="fa-solid <?= e($icon) ?>"></i></span>
                </div>
                <h3 class="ecard-title"><?= $title /* trusted markup from templates */ ?></h3>
            </div>
            <hr class="ecard-divider">
            <div class="ecard-foot">
                <p class="ecard-desc"><?= e($desc) ?></p>
                <?php if ($link): ?>
                    <a href="<?= e($link[1]) ?>" class="ecard-link"><?= e($link[0]) ?><i class="fa-solid fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}
