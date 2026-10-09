<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\ClaimForms\ClaimRepository;
use OpenEMR\Modules\ClaimForms\SignatureStore;

$uid = claimforms_user_id();
$kinds = ['signature' => xlt('Signature'), 'stamp' => xlt('Stamp')];

// Preview: serves only the logged-in user's own image.
if (($_GET['preview'] ?? '') !== '') {
    $kind = (string)$_GET['preview'];
    $path = isset($kinds[$kind]) ? SignatureStore::path($uid, $kind) : null;
    if (!$path) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/png');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

$message = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    claimforms_verify_csrf($_POST['csrf_token_form'] ?? null);
    $kind = (string)($_POST['kind'] ?? '');
    if (isset($kinds[$kind])) {
        if (($_POST['do'] ?? '') === 'delete') {
            SignatureStore::delete($uid, $kind);
            ClaimRepository::event(null, $uid, 'image_removed', $kind);
            $message = xlt('Removed');
        } elseif (isset($_FILES['image'])) {
            $err = SignatureStore::save($uid, $kind, $_FILES['image']);
            if ($err === null) {
                ClaimRepository::event(null, $uid, 'image_saved', $kind);
            }
            $message = $err ?? xlt('Saved');
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('My signature and stamp'); ?></title>
    <?php Header::setupHeader(['common']); ?>
    <style>.preview { max-width: 320px; max-height: 120px; border: 1px dashed #aaa; background: #fff; padding: 4px; }</style>
</head>
<body class="body_top">
<div class="container mt-3" style="max-width: 720px">
    <h3><?php echo xlt('My signature and stamp'); ?></h3>
    <p class="text-muted">
        <?php echo xlt('These images are stamped on claim forms you generate. Only you can use them. Use a PNG with a transparent background if you can; a JPEG with a white background also works.'); ?>
    </p>
    <?php if ($message !== '') { ?><div class="alert alert-info"><?php echo text($message); ?></div><?php } ?>
    <?php foreach ($kinds as $kind => $label) { $has = (bool)SignatureStore::path($uid, $kind); ?>
        <div class="card mb-3"><div class="card-body">
            <h5><?php echo $label; ?></h5>
            <?php if ($has) { ?>
                <p><img class="preview" src="signature.php?preview=<?php echo attr_url($kind); ?>&t=<?php echo time(); ?>" alt=""></p>
            <?php } else { ?>
                <p class="text-muted"><?php echo xlt('None saved.'); ?></p>
            <?php } ?>
            <form method="post" enctype="multipart/form-data" class="form-inline">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr(claimforms_csrf()); ?>">
                <input type="hidden" name="kind" value="<?php echo attr($kind); ?>">
                <input type="file" name="image" accept="image/png,image/jpeg" class="form-control-file mr-2">
                <button class="btn btn-primary btn-sm mr-2" type="submit"><?php echo xlt('Upload'); ?></button>
                <?php if ($has) { ?>
                    <button class="btn btn-outline-danger btn-sm" type="submit" name="do" value="delete"><?php echo xlt('Remove'); ?></button>
                <?php } ?>
            </form>
        </div></div>
    <?php } ?>
    <a href="index.php"><?php echo xlt('Back to claims'); ?></a>
</div>
</body>
</html>
