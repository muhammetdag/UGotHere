<?php
require_once __DIR__ . '/includes/trace.php';
require_once __DIR__ . '/includes/config.php';

$tracedUrl = null;
$error = null;
$shortenedUrlInput = '';
$errorCode = null;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->query("SELECT COUNT(*) AS total_links FROM traces");
    $row = $stmt ? $stmt->fetch() : false;
    $totalLinks = $row && isset($row['total_links']) ? $row['total_links'] : 0;

    $stmt = $pdo->query("SELECT COUNT(*) AS today_links FROM traces WHERE DATE(created_at) = CURDATE()");
    $row = $stmt ? $stmt->fetch() : false;
    $todayLinks = $row && isset($row['today_links']) ? $row['today_links'] : 0;

    $stmt = $pdo->query("SELECT LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(shortened_url, '/', 3), '/', -1)) AS domain, COUNT(*) AS cnt FROM traces GROUP BY domain ORDER BY cnt DESC LIMIT 1");
    $row = $stmt ? $stmt->fetch() : false;
    function getRootDomain($domain) {
        $parts = explode('.', $domain);
        $count = count($parts);
        if ($count >= 2) {
            return $parts[$count-2] . '.' . $parts[$count-1];
        }
        return $domain;
    }
    $popularDomain = $row && isset($row['domain']) ? getRootDomain($row['domain']) : 'N/A';

} catch (\PDOException $e) {
    $totalLinks = 0;
    $todayLinks = 0;
    $popularDomain = 'N/A';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shortenedUrlInput = trim($_POST['shortened_url'] ?? '');

    if (!empty($shortenedUrlInput)) {
        $result = traceLink($shortenedUrlInput);

        if (is_array($result) && isset($result['final_url'])) {
            $tracedUrl = $result['final_url'];
            if ($tracedUrl) {
                $stmt = $pdo->prepare("INSERT INTO traces (shortened_url, final_url, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$shortenedUrlInput, $tracedUrl]);
            }
            header("Location: index.php?traced=" . urlencode($tracedUrl));
            exit;
        } else {
            if (is_array($result) && isset($result['error_code'])) {
                $error = $result['message'];
                $errorCode = $result['error_code'];
            } else {
                $error = is_string($result) ? $result : "Bilinmeyen bir hata oluştu.";
                $errorCode = 'unknownError';
            }
        }
    } else {
        $error = "Lütfen bir URL girin.";
        $errorCode = 'emptyUrlError';
    }
}
if (isset($_GET['traced'])) {
    $tracedUrl = $_GET['traced'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-lang-key="pageTitle">UGotHere - Secure Link Tracing</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔗</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.4s ease-in-out, color 0.4s ease-in-out;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #334155;
        }
        header {
            background-color: #181f2a !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.25);
        }
        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            padding: 2.5rem;
            border-radius: 1.5rem;
            width: 100%;
            max-width: 40rem;
            animation: fadeIn 0.8s ease-out;
            transition: background-color 0.4s, box-shadow 0.4s;
            background-color: #232b3e;
            box-shadow: 0 8px 24px 0 rgba(0,0,0,0.35);
            border: 1px solid #334155;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-field {
            background-color: #232b3e;
            border-color: #334155;
            color: #334155;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.4);
            border-color: #6366f1;
        }
        .button-primary {
            background-image: linear-gradient(to right, #6366f1, #4f46e5);
            color: #fff;
            transition: all 0.3s ease;
            border: none;
        }
        .button-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(99,102,241,0.15), 0 4px 6px -4px rgba(79,70,229,0.15);
        }
        .result-box {
            background-color: #22334d;
            border-color: #22c55e;
            color: #a7ffeb;
        }
        .break-words { color: #a7ffeb; }
        .error-box {
            background-color: #2d1a1a;
            border-color: #ef4444;
            color: #ffb4b4;
        }
        .section-bg { background-color: transparent; }
        .section-card {
            background-color: #232b3e;
            box-shadow: 0 4px 16px -1px rgba(0,0,0,0.22), 0 2px 4px -2px rgba(0,0,0,0.18);
        }
        .section-card h3 {
            color: #fff;
        }
        .section-card p {
            color: #b6c2d1;
        }
        .section-card svg {
            color: #4f8cff;
        }
        .section-title, .section-title h2 {
            color: #fff !important;
        }
        .section-desc, .section-desc p {
            color: #b6c2d1 !important;
        }
        footer {
            background-color: #181f2a;
            color: #64748b;
            border-top: 1px solid #334155;
        }
        .text-gray-900 { color: #fff; }
        .text-gray-600 { color: #b6c2d1; }
        h1, h2, h3, h4, h5, h6 { color: #e2e8f0; }
    </style>
</head>
<body>
    <header class="py-4 px-6 flex justify-center items-center sticky top-0 z-50" style="background-color: rgba(255,255,255,0.8); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div class="max-w-7xl w-full flex justify-between items-center">
            <a href="#" class="text-gray-900 text-2xl font-bold tracking-wide" data-lang-key="brandName" style="color: #fff;">UGotHere</a>
            <div class="flex items-center space-x-4">
                <select id="language-select" onchange="changeLanguage(this.value)" class="bg-gray-100 text-gray-800 py-2 px-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <option value="en">English</option>   
                <option value="tr">Türkçe</option>
                <option value="de">Deutsch</option>
                </select>
            </div>
        </div>
    </header>

    <section class="w-full flex justify-center py-14 px-4" style="border-bottom: 1px solid rgb(51 65 85);">
        <div class="max-w-2xl w-full text-center flex flex-col items-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-4 text-blue-400" data-lang-key="introHeading">See the Real Face of Shortened Links!</h2>
            <p class="text-lg text-gray-300 mb-4 max-w-xl" data-lang-key="introDesc">With UGotHere, safely analyze suspicious links before clicking, learn the real destination and protect yourself.</p>
            <div class="flex flex-col sm:flex-row gap-4 w-full justify-center">
                <button id="show-tool-btn" type="button" class="inline-block px-8 py-4 rounded-xl font-semibold text-white bg-gradient-to-r from-blue-500 to-blue-700 shadow-lg hover:from-blue-600 hover:to-blue-800 transition text-lg" data-lang-key="goToToolButton" onclick="document.getElementById('tool-section').scrollIntoView({ behavior: 'smooth', block: 'start' });">Araca Git</button>
                <a href="https://chrome.google.com/webstore/category/extensions" target="_blank" rel="noopener" class="inline-block px-8 py-4 rounded-xl font-semibold text-blue-400 bg-transparent border-2 border-blue-400 shadow hover:bg-blue-900 transition text-lg" data-lang-key="addToChromeButton">Chrome'a Ekle</a>
            </div>
        </div>
    </section>

    <section class="w-full flex justify-center bg-transparent py-8 px-4">
        <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="stat-card rounded-2xl shadow p-6 flex flex-col items-center"  style="background-color: #232b3e; border: 1px solid #334155;">
                <div class="text-blue-400 text-3xl font-bold mb-2"><svg class="inline w-8 h-8 mr-1" fill="none" stroke="currentColor" viewBox="0 024 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"></path></svg> <?= number_format($totalLinks) ?></div>
                <div class="text-gray-200 text-lg font-semibold" data-lang-key="statTotal">Total Traced Links</div>
            </div>
            <div class="stat-card rounded-2xl shadow p-6 flex flex-col items-center" style="background-color: #232b3e; border: 1px solid #334155;">
                <div class="text-green-400 text-3xl font-bold mb-2"><svg class="inline w-8 h-8 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> <?= number_format($todayLinks) ?></div>
                <div class="text-gray-200 text-lg font-semibold" data-lang-key="statToday">Links Traced Today</div>
            </div>
            <div class="stat-card rounded-2xl shadow p-6 flex flex-col items-center" style="background-color: #232b3e; border: 1px solid #334155;">
                <div class="text-purple-400 text-3xl font-bold mb-2"><svg class="inline w-8 h-8 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 01.88 7.903A4.5 4.5 0 1112 6.5"></path></svg> <?= htmlspecialchars($popularDomain) ?></div>
                <div class="text-gray-200 text-lg font-semibold" data-lang-key="statPopular">Most Popular Domain</div>
            </div>
        </div>
    </section>

    <main class="main-container opacity-100" id="tool-section">
        <div class="card w-full max-w-2xl">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-3" data-lang-key="mainHeading" style="color: #fff;">Reach the Real Link</h1>
                <p class="text-lg text-gray-600 mb-8" data-lang-key="subHeading" style="color: rgb(209 213 219);">Learn where shortened links take you before clicking.</p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="shortened_url" class="sr-only" data-lang-key="inputLabel">Shortened URL</label>
                    <input
                        type="url"
                        id="shortened_url"
                        name="shortened_url"
                        data-lang-placeholder="inputPlaceholder"
                        placeholder="Paste a shortened or suspicious URL here..."
                        class="input-field block w-full px-5 py-4 border rounded-xl shadow-sm sm:text-lg"
                        value="<?= htmlspecialchars($shortenedUrlInput) ?>"
                        required
                    >
                </div>
                <button
                    type="submit"
                    class="button-primary w-full font-bold py-4 px-4 rounded-xl shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-500/50"
                    data-lang-key="traceButton"
                >
                    Trace the Link
                </button>
            </form>

            <?php if ($tracedUrl): ?>
                <div class="result-box mt-8 p-6 border rounded-xl">
                    <h2 class="text-lg font-semibold text-green-800 dark:text-green-300 mb-3" data-lang-key="resultHeading">Safe Destination:</h2>
                    <p class="break-words text-green-900 dark:text-green-200 text-xl font-medium">
                        <a href="<?= htmlspecialchars($tracedUrl) ?>" target="_blank" rel="noopener noreferrer" class="hover:underline">
                            <?= htmlspecialchars($tracedUrl) ?>
                        </a>
                    </p>
                    <button
                        onclick="copyToClipboard('<?= htmlspecialchars($tracedUrl) ?>')"
                        class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-200 dark:text-blue-900 dark:hover:bg-blue-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        data-lang-key="copyButton"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        Copy
                    </button>
                    <div id="copyMessage" class="mt-2 text-sm text-green-600 dark:text-green-400 hidden" data-lang-key="copySuccess">Copied to clipboard!</div>
                </div>
            <?php elseif ($error): ?>
                <div class="error-box mt-8 p-6 border rounded-xl">
                    <h2 class="text-lg font-semibold text-red-800 dark:text-red-300 mb-3" data-lang-key="errorHeading">An Error Occurred:</h2>
                    <p class="text-red-700 dark:text-red-200" id="errorMessage" data-error-code="<?= htmlspecialchars($errorCode) ?>"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <section class="section-bg py-20 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold section-title mb-4" data-lang-key="howItWorksHeading">How UGotHere Works?</h2>
            <p class="text-lg section-desc mb-12" data-lang-key="howItWorksDesc">
                UGotHere, three simple steps to provide security and transparency in the online environment.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                <div class="section-card p-8 rounded-xl">
                    <div class="mb-4"><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg></div>
                    <h3 class="text-xl font-semibold mb-2" data-lang-key="step1Heading">1. Paste the Link</h3>
                    <p data-lang-key="step1Desc">Paste any shortened or suspicious URL into the box above.</p>
                </div>
                <div class="section-card p-8 rounded-xl">
                    <div class="mb-4"><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></div>
                    <h3 class="text-xl font-semibold mb-2" data-lang-key="step2Heading">2. Click Trace</h3>
                    <p data-lang-key="step2Desc">Our system will follow all redirects of the link to find its true destination.</p>
                </div>
                <div class="section-card p-8 rounded-xl">
                    <div class="mb-4"><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                    <h3 class="text-xl font-semibold mb-2" data-lang-key="step3Heading">3. See the Truth</h3>
                    <p data-lang-key="step3Desc">In seconds, you'll safely know where the link will take you.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-8 px-6 mt-auto">
        <div class="max-w-7xl mx-auto text-center">
            <p>
                Crafted with ❤️ by <a href="https://muhammetdag.com" target="_blank" rel="noopener" style="color:#4f8cff; ">Muhammet DAĞ</a>. All rights reserved.
            </p>
            <p class="mt-2 text-sm" data-lang-key="footerMotto">Transparency and Security.</p>
        </div>
    </footer>

    <script>
        let currentTranslations = {};
        async function setLanguage(lang) {
            try {
                const response = await fetch(`assets/lang/${lang}.json?v=${new Date().getTime()}`);
                if (!response.ok) throw new Error(`Çeviri dosyası yüklenemedi: ${lang}.json`);
                currentTranslations = await response.json();
                document.querySelectorAll('[data-lang-key]').forEach(el => {
                    const key = el.getAttribute('data-lang-key');
                    if (currentTranslations[key]) el.textContent = currentTranslations[key];
                });
                document.querySelectorAll('[data-lang-placeholder]').forEach(el => {
                    const key = el.getAttribute('data-lang-placeholder');
                    if (currentTranslations[key]) el.placeholder = currentTranslations[key];
                });
                const errorElement = document.getElementById('errorMessage');
                if (errorElement) {
                    const errorCode = errorElement.getAttribute('data-error-code');
                    if (errorCode && currentTranslations[errorCode]) {
                        errorElement.textContent = currentTranslations[errorCode];
                    } else {
                        errorElement.textContent = "<?= addslashes(htmlspecialchars($error)) ?>";
                    }
                }
                localStorage.setItem('ugothere_language', lang);
                document.documentElement.lang = lang;
            } catch (error) {
                alert("Dil dosyası yüklenemedi veya bir hata oluştu. Lütfen tekrar deneyin.\n" + error);
                console.error("Dil değiştirilirken hata:", error);
            }
        }
        function changeLanguage(lang) {
            setLanguage(lang);
            localStorage.setItem('ugothere_language', lang);
            document.getElementById('language-select').value = lang;
        }
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const message = document.getElementById('copyMessage');
                if (message) {
                    message.classList.remove('hidden');
                    setTimeout(() => message.classList.add('hidden'), 2500);
                }
            }).catch(err => {
                console.error('Kopyalama başarısız oldu:', err);
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
    let savedLanguage = localStorage.getItem('ugothere_language');
    const browserLanguage = navigator.language.split('-')[0];
    const supportedLanguages = ['tr', 'en', 'de'];
    let initialLang = 'en';
    if (savedLanguage && supportedLanguages.includes(savedLanguage)) {
        initialLang = savedLanguage;
    } else if (supportedLanguages.includes(browserLanguage)) {
        initialLang = browserLanguage;
    }
    document.getElementById('language-select').value = initialLang;
    (async () => { await setLanguage(initialLang); })();
}); 
    </script>
</body>
</html>