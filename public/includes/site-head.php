<?php

$pageTitle = $pageTitle ?? 'FurEscue';
$pageDescription = $pageDescription ?? '';
$pageCss = $pageCss ?? [];
$fontsHref = $fontsHref ?? null;
?><!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($pageDescription !== ''): ?>
    <meta
      name="description"
      content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>"
    />
<?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<?php if ($fontsHref !== null): ?>
    <link
      href="<?= htmlspecialchars($fontsHref, ENT_QUOTES, 'UTF-8') ?>"
      rel="stylesheet"
    />
<?php else: ?>
    <link
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
<?php endif; ?>

    <link rel="stylesheet" href="/css/style.css" />
<?php foreach ($pageCss as $pageCssHref): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($pageCssHref, ENT_QUOTES, 'UTF-8') ?>" />
<?php endforeach; ?>

    <script type="importmap">
      {
        "imports": {
          "clsx": "https://esm.sh/clsx@2.1.1",
          "tailwind-merge": "https://esm.sh/tailwind-merge@2.5.5",
          "class-variance-authority": "https://esm.sh/class-variance-authority@0.7.1",
          "lucide": "https://esm.sh/lucide@0.469.0"
        }
      }
    </script>
      <link rel="icon" type="image/png" href="/favicon.png" />
</head>
