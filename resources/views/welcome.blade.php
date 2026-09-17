<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'College Management System') }}</title>

    <link rel="shortcut icon" href="{{ asset('favicon_io/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink: #12233f;
            --muted: #64748b;
            --primary: #3155d9;
            --primary-dark: #203ca7;
            --surface: #ffffff;
            --background: #f5f7fc;
            --border: #e6ebf5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--ink);
            background: var(--background);
            font-family: 'Inter', sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .site-header {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, .94);
            border-bottom: 1px solid rgba(230, 235, 245, .9);
        }

        .nav-container,
        .page-container {
            width: min(1120px, calc(100% - 40px));
            margin: 0 auto;
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 76px;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .brand-mark {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, #536df1, #263fae);
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(49, 85, 217, .25);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link {
            padding: 10px 14px;
            color: var(--muted);
            font-size: .92rem;
            font-weight: 600;
            border-radius: 9px;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary);
            background: #eef2ff;
        }

        .nav-login {
            color: #fff;
            background: var(--primary);
        }

        .nav-login:hover {
            color: #fff;
            background: var(--primary-dark);
        }

        .hero {
            position: relative;
            overflow: hidden;
            padding: 86px 0 92px;
            background: radial-gradient(circle at 80% 10%, rgba(108, 132, 255, .28), transparent 34%),
                linear-gradient(135deg, #101e3b 0%, #1e3470 55%, #3155d9 100%);
        }

        .hero::after {
            position: absolute;
            right: -120px;
            bottom: -190px;
            width: 470px;
            height: 470px;
            content: '';
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 50%;
            box-shadow: 0 0 0 42px rgba(255, 255, 255, .04), 0 0 0 84px rgba(255, 255, 255, .03);
        }

        .hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr);
            align-items: center;
            gap: 64px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #cbd5ff;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            width: 28px;
            height: 2px;
            content: '';
            background: #9eafff;
        }

        .hero h1 {
            max-width: 620px;
            margin: 0;
            color: #fff;
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            line-height: 1.05;
            letter-spacing: -.06em;
        }

        .hero-copy {
            max-width: 540px;
            margin: 24px 0 32px;
            color: #d8e0ff;
            font-size: 1.08rem;
            line-height: 1.75;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            font-size: .92rem;
            font-weight: 700;
            border: 1px solid transparent;
            border-radius: 10px;
            transition: transform .2s ease, background .2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .button-primary {
            color: var(--primary-dark);
            background: #fff;
        }

        .button-primary:hover {
            color: var(--primary-dark);
            background: #eef2ff;
        }

        .button-outline {
            color: #fff;
            border-color: rgba(255, 255, 255, .34);
        }

        .button-outline:hover {
            color: #fff;
            background: rgba(255, 255, 255, .1);
        }

        .dashboard-preview {
            padding: 18px;
            background: rgba(255, 255, 255, .13);
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 22px;
            box-shadow: 0 25px 60px rgba(8, 19, 53, .28);
            backdrop-filter: blur(12px);
        }

        .preview-window {
            padding: 20px;
            background: #fff;
            border-radius: 15px;
        }

        .preview-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .preview-title {
            font-weight: 800;
        }

        .preview-dots {
            display: flex;
            gap: 5px;
        }

        .preview-dots span {
            width: 7px;
            height: 7px;
            background: #cbd5e1;
            border-radius: 50%;
        }

        .preview-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .preview-stat {
            padding: 14px 10px;
            background: #f6f8ff;
            border-radius: 10px;
        }

        .preview-stat strong {
            display: block;
            margin-bottom: 5px;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .preview-stat span {
            color: var(--muted);
            font-size: .67rem;
        }

        .preview-chart {
            height: 110px;
            padding: 16px;
            background: linear-gradient(180deg, #f7f9ff, #fff);
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .chart-lines {
            display: flex;
            align-items: end;
            justify-content: space-around;
            height: 100%;
            gap: 8px;
        }

        .chart-lines span {
            width: 12%;
            background: linear-gradient(180deg, #7187f1, #3155d9);
            border-radius: 5px 5px 2px 2px;
        }

        .features {
            padding: 72px 0 82px;
        }

        .section-heading {
            max-width: 600px;
            margin-bottom: 30px;
        }

        .section-heading h2 {
            margin: 0 0 10px;
            font-size: 2rem;
            letter-spacing: -.04em;
        }

        .section-heading p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .feature-card {
            padding: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(30, 52, 112, .04);
        }

        .feature-icon {
            display: grid;
            width: 42px;
            height: 42px;
            margin-bottom: 18px;
            place-items: center;
            color: var(--primary);
            background: #eef2ff;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 800;
        }

        .feature-card h3 {
            margin: 0 0 8px;
            font-size: 1rem;
        }

        .feature-card p {
            margin: 0;
            color: var(--muted);
            font-size: .88rem;
            line-height: 1.6;
        }

        .site-footer {
            padding: 24px 0;
            color: var(--muted);
            font-size: .82rem;
            text-align: center;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 820px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 42px;
            }

            .hero {
                padding: 64px 0 72px;
            }

            .feature-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 540px) {
            .nav-container,
            .page-container {
                width: min(100% - 28px, 1120px);
            }

            .nav-container {
                min-height: 68px;
            }

            .brand {
                font-size: .9rem;
            }

            .brand-mark {
                width: 34px;
                height: 34px;
            }

            .nav-link {
                padding: 8px 10px;
                font-size: .8rem;
            }

            .hero h1 {
                font-size: 2.65rem;
            }

            .hero-copy {
                font-size: .98rem;
            }

            .preview-stats,
            .feature-grid {
                grid-template-columns: 1fr 1fr;
            }

            .feature-card {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="nav-container">
            <a class="brand" href="{{ url('/') }}">
                <span class="brand-mark">CM</span>
                <span>{{ config('app.name', 'College Management System') }}</span>
            </a>
            <nav class="nav-links" aria-label="Main navigation">
                <a class="nav-link active" href="{{ url('/') }}">Home</a>
                @auth
                    <a class="nav-link" href="{{ url('/home') }}">Dashboard</a>
                @else
                    @if (Route::has('login'))
                        <a class="nav-link nav-login" href="{{ route('login') }}">Login</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="page-container hero-grid">
                <div>
                    <div class="eyebrow">One connected campus</div>
                    <h1>Run your college with clarity.</h1>
                    <p class="hero-copy">
                        Bring students, teachers, classes, attendance, exams, and academic records
                        together in one simple management experience.
                    </p>
                    <div class="hero-actions">
                        @auth
                            <a class="button button-primary" href="{{ url('/home') }}">Open dashboard</a>
                        @else
                            <a class="button button-primary" href="{{ route('login') }}">Login to dashboard</a>
                        @endauth
                        <a class="button button-outline" href="#features">Explore features</a>
                    </div>
                </div>

                <div class="dashboard-preview" aria-label="Dashboard preview">
                    <div class="preview-window">
                        <div class="preview-bar">
                            <span class="preview-title">College overview</span>
                            <span class="preview-dots"><span></span><span></span><span></span></span>
                        </div>
                        <div class="preview-stats">
                            <div class="preview-stat"><strong>24</strong><span>Classes</span></div>
                            <div class="preview-stat"><strong>86</strong><span>Teachers</span></div>
                            <div class="preview-stat"><strong>1.2k</strong><span>Students</span></div>
                        </div>
                        <div class="preview-chart">
                            <div class="chart-lines">
                                <span style="height: 38%;"></span>
                                <span style="height: 58%;"></span>
                                <span style="height: 45%;"></span>
                                <span style="height: 76%;"></span>
                                <span style="height: 64%;"></span>
                                <span style="height: 91%;"></span>
                                <span style="height: 82%;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="features" id="features">
            <div class="page-container">
                <div class="section-heading">
                    <h2>Everything your college needs</h2>
                    <p>Keep daily academic operations organized, visible, and easier to manage.</p>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <div class="feature-icon">01</div>
                        <h3>Student management</h3>
                        <p>Maintain student profiles, academic details, and class assignments.</p>
                    </article>
                    <article class="feature-card">
                        <div class="feature-icon">02</div>
                        <h3>Teacher workspace</h3>
                        <p>Give teachers focused access to courses, attendance, exams, and marks.</p>
                    </article>
                    <article class="feature-card">
                        <div class="feature-icon">03</div>
                        <h3>Academic tracking</h3>
                        <p>Organize sessions, semesters, classes, sections, and course assignments.</p>
                    </article>
                    <article class="feature-card">
                        <div class="feature-icon">04</div>
                        <h3>Exams and results</h3>
                        <p>Manage grading systems, exam rules, marks, and academic progress.</p>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="page-container">&copy; {{ date('Y') }} {{ config('app.name', 'College Management System') }}</div>
    </footer>
</body>
</html>
