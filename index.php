<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $statement = $db->prepare('SELECT id, username, password_hash, must_change_password FROM `user` WHERE username = ?');
    $statement->bind_param('s', $username);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();

    if (strlen($password) <= 72 && $user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['must_change_password'] = (bool) $user['must_change_password'];
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Username or password is incorrect.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in | Member Portal</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        portal: { 950: '#071426', 900: '#092240', 800: '#0f1c2a' }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-portal-900 font-sans text-slate-100">
    <main class="grid min-h-screen lg:grid-cols-[0.92fr_1.08fr]">
        <section class="relative flex min-h-[260px] flex-col justify-between overflow-hidden bg-portal-950 p-7 lg:min-h-screen lg:p-12 xl:px-20">
            <a class="relative z-10 flex items-center gap-3 font-semibold text-white" href="index.php">
                <span class="grid h-8 w-8 place-items-center rounded-lg border border-white/40">M</span>
                Member Portal
            </a>
            <div class="relative z-10 my-10 max-w-xl lg:my-14">
                <p class="mb-5 text-xs font-bold tracking-[0.16em] text-slate-300">YOUR SPACE, SECURED</p>
                <h1 class="text-4xl font-bold leading-tight tracking-tight text-white lg:text-6xl">A simpler way to manage your account.</h1>
                <p class="mt-6 max-w-md leading-7 text-slate-300">Sign in to pick up where you left off, or create an account in just a moment.</p>
            </div>
            <p class="relative z-10 text-xs text-slate-400">~Shauka</p>
        </section>

        <section class="grid place-items-center px-6 py-12 lg:p-12">
            <div class="w-full max-w-sm">
                <p class="mb-3 text-xs font-bold tracking-[0.16em] text-slate-400">WELCOME BACK</p>
                <h2 class="text-3xl font-bold tracking-tight text-white">Sign in</h2>
                <p class="mt-2 mb-7 text-sm leading-6 text-slate-400">Enter your details to access your account.</p>

                <?php if ($error !== ''): ?>
                    <p class="mb-5 rounded-lg border border-red-300/40 bg-red-950/60 px-4 py-3 text-sm text-red-100" role="alert"><?= e($error) ?></p>
                <?php endif; ?>

                <form class="space-y-5" method="post" action="index.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-white" for="username">Username</label>
                        <input class="h-12 w-full rounded-lg border border-slate-500/70 bg-portal-950 px-3.5 text-white outline-none transition placeholder:text-slate-500 focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10" id="username" name="username" type="text" autocomplete="username" required maxlength="32" value="<?= e((string) ($_POST['username'] ?? '')) ?>">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-white" for="password">Password</label>
                        <input class="h-12 w-full rounded-lg border border-slate-500/70 bg-portal-950 px-3.5 text-white outline-none transition placeholder:text-slate-500 focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10" id="password" name="password" type="password" autocomplete="current-password" inputmode="numeric" pattern="[0-9]{6}" required minlength="6" maxlength="6">
                    </div>

                    <button class="flex h-12 w-full items-center justify-between rounded-lg bg-white px-4 font-semibold text-portal-950 transition hover:bg-slate-200" type="submit">
                        Sign in <span aria-hidden="true">&rarr;</span>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-400">New here? <a class="font-semibold text-white hover:text-sky-200" href="register.php">Create an account</a></p>
            </div>
        </section>
    </main>
</body>
</html>
