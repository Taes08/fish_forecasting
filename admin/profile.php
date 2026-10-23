
<?php
require_once __DIR__ . '/../auth/auth_check.php';
requireRole(['admin']);
require_once __DIR__ . '/../config/database.php';

function profileEscape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId < 1) {
    header('Location: ../auth/login.php');
    exit;
}

function loadProfile($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, role, password_hash, profile_picture
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$user = loadProfile($pdo, $userId);

if (!$user) {
    header('Location: ../auth/logout.php');
    exit;
}

$error = '';
$success = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['profile_csrf'], $token)) {
        $error = 'Invalid security token. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'details') {
                $fullName = trim((string)($_POST['full_name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));

                if ($fullName === '' || mb_strlen($fullName) > 150) {
                    throw new RuntimeException(
                        'Enter a valid full name (maximum 150 characters).'
                    );
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
                    strlen($email) > 255) {
                    throw new RuntimeException('Enter a valid email address.');
                }

                $check = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE email = ? AND id <> ?
                    LIMIT 1
                ");
                $check->execute([$email, $userId]);

                if ($check->fetchColumn()) {
                    throw new RuntimeException(
                        'This email address is already registered.'
                    );
                }

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = ?, email = ?
                    WHERE id = ?
                ");
                $stmt->execute([$fullName, $email, $userId]);

                $_SESSION['full_name'] = $fullName;
                $_SESSION['profile_success'] =
                    'Your profile details have been updated.';

            } elseif ($action === 'picture') {
                if (!isset($_FILES['profile_picture']) ||
                    $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(
                        'Please select a valid image to upload.'
                    );
                }

                $file = $_FILES['profile_picture'];

                if ($file['size'] <= 0 || $file['size'] > 2 * 1024 * 1024) {
                    throw new RuntimeException(
                        'Profile picture must be 2 MB or smaller.'
                    );
                }

                if (!is_uploaded_file($file['tmp_name'])) {
                    throw new RuntimeException('Invalid uploaded file.');
                }

                $imageInfo = @getimagesize($file['tmp_name']);

                if (!$imageInfo ||
                    $imageInfo[0] < 1 ||
                    $imageInfo[1] < 1 ||
                    $imageInfo[0] > 5000 ||
                    $imageInfo[1] > 5000) {
                    throw new RuntimeException(
                        'The image is invalid or its dimensions are too large.'
                    );
                }

                $mime = (new finfo(FILEINFO_MIME_TYPE))
                    ->file($file['tmp_name']);

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowed[$mime])) {
                    throw new RuntimeException(
                        'Only JPG, PNG, and WebP images are allowed.'
                    );
                }

                $uploadDirectory =
                    __DIR__ . '/../uploads/profile_pictures/';

                if (!is_dir($uploadDirectory) &&
                    !mkdir($uploadDirectory, 0755, true) &&
                    !is_dir($uploadDirectory)) {
                    throw new RuntimeException(
                        'Unable to create the upload directory.'
                    );
                }

                $filename = 'user_' . $userId . '_' .
                    bin2hex(random_bytes(12)) . '.' . $allowed[$mime];

                $destination = $uploadDirectory . $filename;
                $relativePath = 'uploads/profile_pictures/' . $filename;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new RuntimeException(
                        'Unable to save your profile picture.'
                    );
                }

                try {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET profile_picture = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$relativePath, $userId]);
                } catch (Throwable $exception) {
                    @unlink($destination);
                    throw $exception;
                }

                $oldPicture = $user['profile_picture'] ?? '';

                if ($oldPicture &&
                    preg_match(
                        '~^uploads/profile_pictures/[a-zA-Z0-9_.-]+$~',
                        $oldPicture
                    )) {
                    $oldFile = __DIR__ . '/../' . $oldPicture;

                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }

                $_SESSION['profile_success'] =
                    'Your profile picture has been updated.';

            } elseif ($action === 'password') {
                $currentPassword =
                    (string)($_POST['current_password'] ?? '');
                $newPassword =
                    (string)($_POST['new_password'] ?? '');
                $confirmPassword =
                    (string)($_POST['confirm_password'] ?? '');

                if (!password_verify(
                    $currentPassword,
                    $user['password_hash']
                )) {
                    throw new RuntimeException(
                        'Your current password is incorrect.'
                    );
                }

                if (strlen($newPassword) < 8) {
                    throw new RuntimeException(
                        'New password must contain at least 8 characters.'
                    );
                }

                if ($newPassword !== $confirmPassword) {
                    throw new RuntimeException(
                        'New passwords do not match.'
                    );
                }

                if (password_verify(
                    $newPassword,
                    $user['password_hash']
                )) {
                    throw new RuntimeException(
                        'Choose a password different from your current password.'
                    );
                }

                $hash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE id = ?
                ");
                $stmt->execute([$hash, $userId]);

                $_SESSION['profile_success'] =
                    'Your password has been changed successfully.';

            } else {
                throw new RuntimeException('Invalid profile action.');
            }

            header('Location: profile.php');
            exit;

        } catch (PDOException $exception) {
            error_log(
                'FishCast profile database error: ' .
                $exception->getMessage()
            );
            $error = 'Unable to update your account. Please try again.';

        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }

    $user = loadProfile($pdo, $userId);
}

$parts = preg_split('/\s+/', trim($user['full_name']));
$initials = '';

foreach (array_slice($parts, 0, 2) as $part) {
    if ($part !== '') {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
}

$initials = $initials ?: 'FC';

$picture = $user['profile_picture'] ?? '';
$pictureUrl = '';

if ($picture &&
    preg_match(
        '~^uploads/profile_pictures/[a-zA-Z0-9_.-]+$~',
        $picture
    ) &&
    is_file(__DIR__ . '/../' . $picture)) {
    $pictureUrl = '../' . $picture;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile | FishCast</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F5FAFB;
            color: #0B3954;
        }

        h1, h2, h3 {
            font-family: 'Outfit', sans-serif;
        }

        .panel {
            background: white;
            border: 1px solid #E1ECEE;
            border-radius: 20px;
            box-shadow: 0 4px 18px rgba(11,57,84,.04);
        }

        .field {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #CBD5E1;
            border-radius: 12px;
            outline: none;
            transition: .2s;
            color: #0B3954;
        }

        .field:focus {
            border-color: #087E8B;
            box-shadow: 0 0 0 4px rgba(8,126,139,.1);
        }

        .btn-primary {
            background: #087E8B;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: .2s;
        }

        .btn-primary:hover {
            background: #0B3954;
        }

        .swal2-popup {
            border-radius: 20px !important;
            font-family: 'Inter', sans-serif !important;
        }
    </style>
</head>

<body>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="lg:ml-64 min-h-screen">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="p-5 lg:p-8 xl:p-10 max-w-6xl mx-auto space-y-7">

        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#087E8B] mb-2">
                FishCast / Account Settings
            </p>

            <h1 class="text-3xl lg:text-4xl font-extrabold text-[#0B3954]">
                My Profile
            </h1>

            <p class="text-slate-500 mt-2">
                Manage your personal information, photo, and account security.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- PROFILE PICTURE -->
            <section class="panel p-6 text-center">

                <div class="relative w-32 h-32 mx-auto mb-5">

                    <div
                        id="avatarFallback"
                        class="w-32 h-32 rounded-full bg-[#087E8B]
                               flex items-center justify-center
                               text-white text-4xl font-bold
                               <?= $pictureUrl ? 'hidden' : '' ?>">
                        <?= profileEscape($initials) ?>
                    </div>

                    <img
                        id="avatarPreview"
                        src="<?= profileEscape($pictureUrl) ?>"
                        alt="Profile picture"
                        class="w-32 h-32 rounded-full object-cover border-4 border-[#E8F7F6]
                               <?= $pictureUrl ? '' : 'hidden' ?>">
                </div>

                <h2 class="text-xl font-bold text-[#0B3954]">
                    <?= profileEscape($user['full_name']) ?>
                </h2>

                <p class="text-sm text-slate-500 mt-1 break-all">
                    <?= profileEscape($user['email']) ?>
                </p>

                <span class="inline-flex items-center gap-2 mt-4 px-3 py-1.5
                             rounded-full bg-[#E8F7F6] text-[#087E8B]
                             text-xs font-bold">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    <?= profileEscape(ucfirst($user['role'])) ?>
                </span>

                <form
                    id="pictureForm"
                    method="POST"
                    enctype="multipart/form-data"
                    class="mt-6 text-left">

                    <input type="hidden" name="csrf_token"
                           value="<?= profileEscape($_SESSION['profile_csrf']) ?>">
                    <input type="hidden" name="action" value="picture">

                    <label class="block text-sm font-semibold mb-2">
                        Profile Picture
                    </label>

                    <input
                        type="file"
                        id="profilePicture"
                        name="profile_picture"
                        accept="image/jpeg,image/png,image/webp"
                        required
                        class="block w-full text-xs text-slate-600
                               file:mr-3 file:py-2.5 file:px-3
                               file:rounded-lg file:border-0
                               file:bg-[#E8F7F6] file:text-[#087E8B]
                               file:font-semibold">

                    <p class="text-xs text-slate-400 mt-3">
                        JPG, PNG, or WebP. Maximum 2 MB.
                    </p>

                    <button type="submit" class="btn-primary w-full mt-5">
                        <i data-lucide="image-up" class="w-4 h-4"></i>
                        Upload Photo
                    </button>

                </form>
            </section>

            <!-- ACCOUNT SETTINGS -->
            <div class="lg:col-span-2 space-y-6">

                <!-- PERSONAL INFORMATION -->
                <section class="panel p-6 lg:p-8">

                    <div class="flex items-center gap-3 mb-7">

                        <div class="p-3 rounded-xl bg-[#E8F7F6]">
                            <i data-lucide="user-round"
                               class="w-6 h-6 text-[#087E8B]"></i>
                        </div>

                        <div>
                            <h2 class="text-xl font-bold">
                                Personal Information
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">
                                Update your account details.
                            </p>
                        </div>

                    </div>

                    <form method="POST" id="detailsForm" class="space-y-5">

                        <input type="hidden" name="csrf_token"
                               value="<?= profileEscape($_SESSION['profile_csrf']) ?>">
                        <input type="hidden" name="action" value="details">

                        <div>
                            <label for="fullName"
                                   class="block text-sm font-semibold mb-2">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="fullName"
                                name="full_name"
                                value="<?= profileEscape($user['full_name']) ?>"
                                maxlength="150"
                                required
                                class="field">
                        </div>

                        <div>
                            <label for="email"
                                   class="block text-sm font-semibold mb-2">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= profileEscape($user['email']) ?>"
                                maxlength="255"
                                required
                                class="field">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2">
                                Account Role
                            </label>

                            <div class="field bg-slate-50 text-slate-500">
                                <?= profileEscape(ucfirst($user['role'])) ?>
                            </div>

                            <p class="text-xs text-slate-400 mt-2">
                                Account roles cannot be changed from this page.
                            </p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="btn-primary">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                Save Changes
                            </button>
                        </div>

                    </form>

                </section>

                <!-- CHANGE PASSWORD -->
                <section class="panel p-6 lg:p-8">

                    <div class="flex items-center gap-3 mb-7">

                        <div class="p-3 rounded-xl bg-[#E8F7F6]">
                            <i data-lucide="lock-keyhole"
                               class="w-6 h-6 text-[#087E8B]"></i>
                        </div>

                        <div>
                            <h2 class="text-xl font-bold">
                                Account Security
                            </h2>

                            <p class="text-sm text-slate-500 mt-1">
                                Change your password to keep your account secure.
                            </p>
                        </div>

                    </div>

                    <form method="POST" id="passwordForm" class="space-y-5">

                        <input type="hidden" name="csrf_token"
                               value="<?= profileEscape($_SESSION['profile_csrf']) ?>">
                        <input type="hidden" name="action" value="password">

                        <div>
                            <label for="currentPassword"
                                   class="block text-sm font-semibold mb-2">
                                Current Password
                            </label>

                            <input
                                type="password"
                                id="currentPassword"
                                name="current_password"
                                autocomplete="current-password"
                                required
                                class="field">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                            <div>
                                <label for="newPassword"
                                       class="block text-sm font-semibold mb-2">
                                    New Password
                                </label>

                                <input
                                    type="password"
                                    id="newPassword"
                                    name="new_password"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                    class="field">
                            </div>

                            <div>
                                <label for="confirmPassword"
                                       class="block text-sm font-semibold mb-2">
                                    Confirm Password
                                </label>

                                <input
                                    type="password"
                                    id="confirmPassword"
                                    name="confirm_password"
                                    minlength="8"
                                    autocomplete="new-password"
                                    required
                                    class="field">
                            </div>

                        </div>

                        <p class="text-xs text-slate-400">
                            Use at least 8 characters for your new password.
                        </p>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="btn-primary">
                                <i data-lucide="key-round" class="w-4 h-4"></i>
                                Update Password
                            </button>
                        </div>

                    </form>

                </section>

            </div>

        </div>

    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !overlay) return;

    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

const pictureInput = document.getElementById('profilePicture');
let previewUrl = null;

pictureInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    const allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if (!allowed.includes(file.type) || file.size > 2 * 1024 * 1024) {
        this.value = '';

        Swal.fire({
            title: 'Invalid Image',
            text: 'Choose a JPG, PNG, or WebP image under 2 MB.',
            icon: 'warning',
            confirmButtonColor: '#087E8B'
        });
        return;
    }

    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
    }

    previewUrl = URL.createObjectURL(file);

    const img = document.getElementById('avatarPreview');
    img.src = previewUrl;
    img.classList.remove('hidden');

    document.getElementById('avatarFallback').classList.add('hidden');
});

document.getElementById('pictureForm').addEventListener(
    'submit',
    function(event) {
        event.preventDefault();

        Swal.fire({
            title: 'Upload Profile Picture?',
            text: 'Your current profile picture will be replaced.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Upload',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#087E8B',
            cancelButtonColor: '#64748B'
        }).then(result => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    }
);

document.getElementById('detailsForm').addEventListener(
    'submit',
    function(event) {
        event.preventDefault();

        Swal.fire({
            title: 'Save Profile Changes?',
            text: 'Your account information will be updated.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#087E8B',
            cancelButtonColor: '#64748B'
        }).then(result => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    }
);

document.getElementById('passwordForm').addEventListener(
    'submit',
    function(event) {
        event.preventDefault();

        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            Swal.fire({
                title: 'Passwords Do Not Match',
                text: 'Please make sure both new password fields match.',
                icon: 'warning',
                confirmButtonColor: '#087E8B'
            });
            return;
        }

        Swal.fire({
            title: 'Change Password?',
            text: 'Your account password will be updated.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Update Password',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#087E8B',
            cancelButtonColor: '#64748B'
        }).then(result => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    }
);

const successMessage = <?= json_encode(
    $success,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

const errorMessage = <?= json_encode(
    $error,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

if (successMessage) {
    Swal.fire({
        title: 'Success!',
        text: successMessage,
        icon: 'success',
        iconColor: '#087E8B',
        confirmButtonColor: '#087E8B',
        timer: 2500,
        timerProgressBar: true
    });
}

if (errorMessage) {
    Swal.fire({
        title: 'Unable to Update Profile',
        text: errorMessage,
        icon: 'error',
        confirmButtonColor: '#087E8B'
    });
}
</script>

</body>
</html>
