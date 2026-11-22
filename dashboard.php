<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_auth();

$search = trim((string) ($_GET['search'] ?? ''));
if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}

$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = ($page === false || $page === null || $page < 1) ? 1 : $page;
$postsPerPage = 5;
$offset = ($page - 1) * $postsPerPage;
$userId = (int) $_SESSION['user_id'];

if ($search !== '') {
    $searchPattern = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    $countStatement = $db->prepare(
        'SELECT COUNT(*) AS total FROM `post`
         WHERE user_id = ? AND (title LIKE ? ESCAPE \'!\' OR content LIKE ? ESCAPE \'!\')'
    );
    $countStatement->bind_param('iss', $userId, $searchPattern, $searchPattern);
} else {
    $countStatement = $db->prepare('SELECT COUNT(*) AS total FROM `post` WHERE user_id = ?');
    $countStatement->bind_param('i', $userId);
}
$countStatement->execute();
$totalPosts = (int) $countStatement->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int) ceil($totalPosts / $postsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $postsPerPage;

if ($search !== '') {
    $statement = $db->prepare(
        'SELECT id, title, content, created_at, updated_at FROM `post`
         WHERE user_id = ? AND (title LIKE ? ESCAPE \'!\' OR content LIKE ? ESCAPE \'!\')
         ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
    );
    $statement->bind_param('issii', $userId, $searchPattern, $searchPattern, $postsPerPage, $offset);
} else {
    $statement = $db->prepare(
        'SELECT id, title, content, created_at, updated_at FROM `post`
         WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
    );
    $statement->bind_param('iii', $userId, $postsPerPage, $offset);
}
$statement->execute();
$posts = $statement->get_result()->fetch_all(MYSQLI_ASSOC);

$paginationQuery = $search !== '' ? ['search' => $search] : [];
$rangeStart = $totalPosts === 0 ? 0 : $offset + 1;
$rangeEnd = min($offset + $postsPerPage, $totalPosts);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your account | Member Portal</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { portal: { 950: '#071426', 900: '#092240', 800: '#0f1c2a' } } } }
        }
    </script>
</head>
<body class="min-h-screen bg-portal-900 font-sans text-slate-100">
    <header class="flex h-20 items-center justify-between border-b border-slate-600/50 bg-portal-950 px-6 lg:px-20">
        <a class="flex items-center gap-3 font-semibold text-white" href="dashboard.php"><span class="grid h-8 w-8 place-items-center rounded-lg border border-slate-400/60">M</span> Member Portal</a>
        <form method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-white/5 hover:text-white" type="submit">Sign out</button>
        </form>
    </header>
    <main class="mx-auto w-[calc(100%-3rem)] max-w-4xl py-14 lg:py-20">
        <p class="mb-4 text-xs font-bold tracking-[0.16em] text-slate-400">YOUR ACCOUNT</p>
        <h1 class="text-4xl font-bold tracking-tight text-white lg:text-5xl">Welcome, <?= e((string) $_SESSION['username']) ?>.</h1>
        <p class="mt-3 text-sm text-slate-400">You’re signed in and ready to go.</p>
        <section class="mt-9 grid gap-5 rounded-xl border border-slate-500/50 bg-portal-800 p-6 sm:grid-cols-[auto_1fr_1fr] sm:items-center">
            <span class="grid h-12 w-12 place-items-center rounded-full bg-white text-lg font-bold text-portal-950" aria-hidden="true"><?= e(strtoupper(substr((string) $_SESSION['username'], 0, 1))) ?></span>
            <div>
                <p class="card-label">Username</p>
                <p class="card-value"><?= e((string) $_SESSION['username']) ?></p>
            </div>
            <div>
                <p class="card-label">Account ID</p>
                <p class="card-value"><?= e((string) $_SESSION['user_id']) ?></p>
            </div>
        </section>

        <section class="posts-section">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">YOUR WRITING</p>
                    <h2>Blog posts</h2>
                </div>
                <span class="post-count"><?= $rangeStart ?>–<?= $rangeEnd ?> of <?= $totalPosts ?> <?= $totalPosts === 1 ? 'post' : 'posts' ?></span>
            </div>

            <?php if (isset($_GET['saved'])): ?>
                <p class="success" role="status">Your post was saved.</p>
            <?php elseif (isset($_GET['deleted'])): ?>
                <p class="success" role="status">Your post was deleted.</p>
            <?php endif; ?>

            <form class="search-form" method="get" action="dashboard.php" role="search">
                <label class="search-field" for="post-search">
                    <span aria-hidden="true">⌕</span>
                    <input id="post-search" name="search" type="search" maxlength="100" value="<?= e($search) ?>" placeholder="Search posts by title or content">
                </label>
                <button class="search-button" type="submit">Search</button>
                <?php if ($search !== ''): ?>
                    <a class="clear-search" href="dashboard.php">Clear</a>
                <?php endif; ?>
            </form>

            <form class="post-form" method="post" action="posts.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create">
                <label for="new-title">Title</label>
                <input id="new-title" name="title" type="text" maxlength="160" required placeholder="Give your post a title">
                <label for="new-content">Content</label>
                <textarea id="new-content" name="content" rows="5" required placeholder="Write something..."></textarea>
                <button class="button" type="submit">Publish post <span aria-hidden="true">&rarr;</span></button>
            </form>

            <div class="post-list">
                <?php if (!$posts): ?>
                    <?php if ($search !== ''): ?>
                        <p class="empty-posts">No posts match “<?= e($search) ?>”. Try another search.</p>
                    <?php else: ?>
                        <p class="empty-posts">No posts yet. Your first post can start here.</p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php foreach ($posts as $post): ?>
                    <article class="post-card">
                        <form class="post-form" method="post" action="posts.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="post_id" value="<?= e((string) $post['id']) ?>">
                            <label for="title-<?= e((string) $post['id']) ?>">Title</label>
                            <input id="title-<?= e((string) $post['id']) ?>" name="title" type="text" maxlength="160" required value="<?= e($post['title']) ?>">
                            <label for="content-<?= e((string) $post['id']) ?>">Content</label>
                            <textarea id="content-<?= e((string) $post['id']) ?>" name="content" rows="5" required><?= e($post['content']) ?></textarea>
                            <p class="post-date">Created <?= e(date('M j, Y \a\t g:i a', strtotime($post['created_at']))) ?></p>
                            <div class="post-actions">
                                <button class="button" type="submit">Save changes</button>
                            </div>
                        </form>
                        <form class="delete-form" method="post" action="posts.php" onsubmit="return confirm('Delete this post? This cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="post_id" value="<?= e((string) $post['id']) ?>">
                            <button class="delete-button" type="submit">Delete post</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Blog post pages">
                    <?php
                    $previousQuery = $paginationQuery;
                    $previousQuery['page'] = $page - 1;
                    ?>
                    <?php if ($page > 1): ?>
                        <a class="page-link" href="dashboard.php?<?= e(http_build_query($previousQuery)) ?>" rel="prev" aria-label="Previous page">&larr; Previous</a>
                    <?php else: ?>
                        <span class="page-link is-disabled" aria-disabled="true">&larr; Previous</span>
                    <?php endif; ?>

                    <div class="page-numbers">
                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <?php
                            $pageQuery = $paginationQuery;
                            $pageQuery['page'] = $pageNumber;
                            ?>
                            <?php if ($pageNumber === $page): ?>
                                <span class="page-number is-current" aria-current="page"><?= $pageNumber ?></span>
                            <?php else: ?>
                                <a class="page-number" href="dashboard.php?<?= e(http_build_query($pageQuery)) ?>"><?= $pageNumber ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <?php
                    $nextQuery = $paginationQuery;
                    $nextQuery['page'] = $page + 1;
                    ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="page-link" href="dashboard.php?<?= e(http_build_query($nextQuery)) ?>" rel="next" aria-label="Next page">Next &rarr;</a>
                    <?php else: ?>
                        <span class="page-link is-disabled" aria-disabled="true">Next &rarr;</span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
