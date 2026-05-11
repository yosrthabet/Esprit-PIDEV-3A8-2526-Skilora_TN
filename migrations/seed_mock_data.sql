-- ============================================================
-- SKILORA MOCK DATA SEED
-- Users: 1=admin, 2=user, 3=employer, 4=trainer
-- Run: /Applications/XAMPP/xamppfiles/bin/mysql -u root -h 127.0.0.1 skilorafinal < migrations/seed_mock_data.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─── WALLETS ────────────────────────────────────────────────
INSERT IGNORE INTO finance_wallets (user_id, balance, currency, created_at, updated_at) VALUES
(2, 1250.00, 'TND', NOW(), NOW()),
(3, 8500.00, 'TND', NOW(), NOW()),
(4, 3200.00, 'TND', NOW(), NOW());

-- ─── FORMATIONS (by trainer=4) ─────────────────────────────
INSERT INTO formations (title, description, category, level, duration_hours, price_amount, status, created_at, updated_at, trainer_id) VALUES
('Full-Stack Web Development', 'Learn HTML, CSS, JavaScript, PHP, Symfony and build production-ready applications from scratch.', 'Development', 'intermediate', 40, 299.00, 'published', DATE_SUB(NOW(), INTERVAL 30 DAY), NOW(), 4),
('Python for Data Science', 'Master Python, NumPy, Pandas, and machine learning fundamentals with hands-on projects.', 'Data Science', 'beginner', 25, 199.00, 'published', DATE_SUB(NOW(), INTERVAL 25 DAY), NOW(), 4),
('UI/UX Design Masterclass', 'Design beautiful interfaces with Figma, understand user psychology, and build a portfolio.', 'Design', 'beginner', 20, 149.00, 'published', DATE_SUB(NOW(), INTERVAL 20 DAY), NOW(), 4),
('DevOps & CI/CD Pipeline', 'Docker, Kubernetes, GitHub Actions, and deployment automation for modern teams.', 'DevOps', 'advanced', 35, 349.00, 'published', DATE_SUB(NOW(), INTERVAL 15 DAY), NOW(), 4),
('Cybersecurity Fundamentals', 'OWASP Top 10, penetration testing basics, secure coding, and incident response.', 'Security', 'intermediate', 30, 279.00, 'published', DATE_SUB(NOW(), INTERVAL 10 DAY), NOW(), 4),
('Mobile App Development', 'Build cross-platform mobile apps with React Native and deploy to App Store and Google Play.', 'Mobile', 'intermediate', 28, 249.00, 'draft', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW(), 4);

-- ─── FORMATION MODULES ─────────────────────────────────────
-- Get IDs dynamically using subqueries based on title
INSERT INTO formation_modules (title, description, position, duration_minutes, created_at, formation_id) VALUES
('HTML & CSS Fundamentals', 'Build layouts with semantic HTML and modern CSS.', 1, 120, NOW(), (SELECT id FROM formations WHERE title='Full-Stack Web Development')),
('JavaScript Deep Dive', 'ES6+, async/await, DOM manipulation.', 2, 180, NOW(), (SELECT id FROM formations WHERE title='Full-Stack Web Development')),
('PHP & Symfony Framework', 'MVC, routing, Doctrine ORM, Twig templates.', 3, 240, NOW(), (SELECT id FROM formations WHERE title='Full-Stack Web Development')),
('Capstone Project', 'Build and deploy a full-stack application.', 4, 300, NOW(), (SELECT id FROM formations WHERE title='Full-Stack Web Development')),
('Python Basics', 'Variables, functions, control flow, OOP.', 1, 120, NOW(), (SELECT id FROM formations WHERE title='Python for Data Science')),
('Data Analysis with Pandas', 'DataFrames, cleaning, visualization.', 2, 180, NOW(), (SELECT id FROM formations WHERE title='Python for Data Science')),
('Machine Learning Intro', 'Scikit-learn, regression, classification.', 3, 200, NOW(), (SELECT id FROM formations WHERE title='Python for Data Science')),
('Design Thinking', 'User research, personas, journey maps.', 1, 90, NOW(), (SELECT id FROM formations WHERE title='UI/UX Design Masterclass')),
('Figma Prototyping', 'Components, auto layout, interactive prototypes.', 2, 150, NOW(), (SELECT id FROM formations WHERE title='UI/UX Design Masterclass')),
('Docker & Containers', 'Images, volumes, docker-compose.', 1, 120, NOW(), (SELECT id FROM formations WHERE title='DevOps & CI/CD Pipeline')),
('Kubernetes Orchestration', 'Pods, services, deployments, scaling.', 2, 180, NOW(), (SELECT id FROM formations WHERE title='DevOps & CI/CD Pipeline')),
('CI/CD with GitHub Actions', 'Workflows, testing, automated deployments.', 3, 150, NOW(), (SELECT id FROM formations WHERE title='DevOps & CI/CD Pipeline')),
('OWASP Top 10', 'Common vulnerabilities and mitigations.', 1, 120, NOW(), (SELECT id FROM formations WHERE title='Cybersecurity Fundamentals')),
('Penetration Testing', 'Reconnaissance, exploitation, reporting.', 2, 180, NOW(), (SELECT id FROM formations WHERE title='Cybersecurity Fundamentals'));

-- ─── ENROLLMENTS ────────────────────────────────────────────
INSERT INTO formation_enrollments (user_id, formation_id, status, enrolled_at, completed_at) VALUES
(2, (SELECT id FROM formations WHERE title='Full-Stack Web Development'), 'active', DATE_SUB(NOW(), INTERVAL 20 DAY), NULL),
(2, (SELECT id FROM formations WHERE title='Python for Data Science'), 'completed', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, (SELECT id FROM formations WHERE title='Cybersecurity Fundamentals'), 'active', DATE_SUB(NOW(), INTERVAL 7 DAY), NULL);

-- ─── FORMATION REVIEWS ──────────────────────────────────────
INSERT INTO formation_reviews (rating, comment, created_at, updated_at, formation_id, user_id) VALUES
(5, 'Excellent course! The Symfony section was incredibly practical and well-structured.', DATE_SUB(NOW(), INTERVAL 3 DAY), NOW(), (SELECT id FROM formations WHERE title='Full-Stack Web Development'), 2),
(4, 'Great introduction to data science. Would love more advanced ML content.', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), (SELECT id FROM formations WHERE title='Python for Data Science'), 2);

-- ─── QUIZZES ────────────────────────────────────────────────
INSERT INTO formation_quizzes (title, description, passing_score, time_limit_minutes, is_published, created_at, formation_id) VALUES
('Web Dev Midterm', 'Test your HTML, CSS, and JavaScript knowledge.', 70, 30, 1, NOW(), (SELECT id FROM formations WHERE title='Full-Stack Web Development')),
('Python Basics Quiz', 'Variables, functions, and data types.', 60, 20, 1, NOW(), (SELECT id FROM formations WHERE title='Python for Data Science')),
('Security Fundamentals', 'OWASP Top 10 assessment.', 75, 25, 1, NOW(), (SELECT id FROM formations WHERE title='Cybersecurity Fundamentals'));

-- ─── QUIZ QUESTIONS ─────────────────────────────────────────
INSERT INTO formation_quiz_questions (quiz_id, question_text, choices, correct_index, points, explanation, position) VALUES
((SELECT id FROM formation_quizzes WHERE title='Web Dev Midterm'), 'What does HTML stand for?', '["HyperText Markup Language","High Tech Modern Language","HyperTransfer Markup Language","Home Tool Markup Language"]', 0, 10, 'HTML = HyperText Markup Language', 1),
((SELECT id FROM formation_quizzes WHERE title='Web Dev Midterm'), 'Which CSS property controls text size?', '["text-size","font-size","text-style","font-weight"]', 1, 10, 'font-size is the correct CSS property.', 2),
((SELECT id FROM formation_quizzes WHERE title='Web Dev Midterm'), 'What is the === operator in JavaScript?', '["Assignment","Loose equality","Strict equality","Not equal"]', 2, 10, 'Strict equality checks both value and type.', 3),
((SELECT id FROM formation_quizzes WHERE title='Python Basics Quiz'), 'Which keyword defines a function in Python?', '["function","func","def","define"]', 2, 10, 'def is used to define functions in Python.', 1),
((SELECT id FROM formation_quizzes WHERE title='Python Basics Quiz'), 'What is the output of print(type([]))?', '["<class ''dict''>","<class ''list''>","<class ''tuple''>","<class ''set''>"]', 1, 10, '[] creates a list in Python.', 2),
((SELECT id FROM formation_quizzes WHERE title='Security Fundamentals'), 'Which OWASP category covers SQL Injection?', '["Broken Authentication","Injection","XSS","CSRF"]', 1, 10, 'SQL Injection falls under the Injection category.', 1),
((SELECT id FROM formation_quizzes WHERE title='Security Fundamentals'), 'What does CSRF stand for?', '["Cross-Site Request Forgery","Cross-Server Response Failure","Client-Side Request Filter","Cross-Site Resource Fetch"]', 0, 10, 'CSRF = Cross-Site Request Forgery.', 2);

-- ─── COMMUNITY POSTS ────────────────────────────────────────
INSERT INTO community_feed_posts (content, status, likes_count, comments_count, shares_count, reports_count, visibility, created_at, updated_at, author_id) VALUES
('Just completed the Full-Stack Web Development course on Skilora! The Symfony module was a game-changer for my career. Highly recommend it to anyone looking to level up. 🚀', 'published', 3, 2, 1, 0, 'public', DATE_SUB(NOW(), INTERVAL 4 DAY), NOW(), 2),
('Looking for a talented full-stack developer to join our team at TechCorp Tunisia. We offer competitive salary, remote-first culture, and amazing growth opportunities. Check our job listings!', 'published', 2, 1, 0, 0, 'public', DATE_SUB(NOW(), INTERVAL 3 DAY), NOW(), 3),
('Pro tip for learners: Don''t just watch tutorials — build projects! I learned more from my capstone project than 20 hours of video content. What''s your best learning hack?', 'published', 5, 3, 2, 0, 'public', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), 2),
('Excited to announce our new Cybersecurity Fundamentals course! Covers OWASP Top 10, pen testing, and secure coding. Launching next week with early bird pricing.', 'published', 4, 2, 1, 0, 'public', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), 4),
('Anyone else attending the Tunis Tech Meetup this weekend? Would love to connect with fellow Skilora members there! 🇹🇳', 'published', 1, 0, 0, 0, 'public', DATE_SUB(NOW(), INTERVAL 12 HOUR), NOW(), 2),
('We just crossed 150 job listings on Skilora! Thank you to all employers who trust our platform. More exciting features coming soon.', 'published', 6, 1, 3, 0, 'public', DATE_SUB(NOW(), INTERVAL 6 HOUR), NOW(), 3),
('Finished grading the first batch of Python quizzes. Average score: 82%! Proud of my students. Keep pushing! 💪', 'published', 3, 2, 0, 0, 'public', DATE_SUB(NOW(), INTERVAL 3 HOUR), NOW(), 4),
('Hot take: TypeScript > JavaScript for any project larger than a todo app. Fight me in the comments. 😄', 'published', 8, 4, 2, 0, 'public', DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW(), 2);

-- ─── COMMUNITY COMMENTS ─────────────────────────────────────
INSERT INTO community_feed_comments (post_id, author_id, content, created_at) VALUES
((SELECT id FROM community_feed_posts WHERE content LIKE 'Just completed%' LIMIT 1), 3, 'Congratulations! We''re always looking for talented developers who went through solid training.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Just completed%' LIMIT 1), 4, 'So glad you enjoyed the course! The Symfony module took months to build.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Looking for a talented%' LIMIT 1), 2, 'Just applied! Love the remote-first approach.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Pro tip for learners%' LIMIT 1), 4, 'Absolutely! Project-based learning is the core of all my courses.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Pro tip for learners%' LIMIT 1), 3, 'We actually prefer candidates who show real projects over certificates.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Pro tip for learners%' LIMIT 1), 2, 'Thanks for the validation! Building in public changed everything for me.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Excited to announce%' LIMIT 1), 2, 'Can''t wait! Already enrolled in the early access list.', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Excited to announce%' LIMIT 1), 3, 'We''d love to sponsor some seats for our dev team!', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'We just crossed%' LIMIT 1), 4, 'Amazing milestone! The platform keeps getting better.', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Finished grading%' LIMIT 1), 2, '82% average is impressive! That quiz was tough.', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Finished grading%' LIMIT 1), 3, 'Our interns scored 90%+ thanks to your course. Great work!', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Hot take:%' LIMIT 1), 3, 'Agree. We migrated our entire stack to TypeScript last year.', DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Hot take:%' LIMIT 1), 4, 'For teaching, I still prefer vanilla JS first so students understand the fundamentals.', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Hot take:%' LIMIT 1), 2, 'Fair point! Fundamentals first, TypeScript second.', DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Hot take:%' LIMIT 1), 3, 'We actually use TypeScript in our interview assessments now.', DATE_SUB(NOW(), INTERVAL 5 MINUTE));

-- ─── COMMUNITY REACTIONS ────────────────────────────────────
INSERT INTO community_feed_reactions (post_id, user_id, type, created_at) VALUES
((SELECT id FROM community_feed_posts WHERE content LIKE 'Just completed%' LIMIT 1), 3, 'like', DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Just completed%' LIMIT 1), 4, 'heart', DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Looking for a talented%' LIMIT 1), 2, 'like', DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Looking for a talented%' LIMIT 1), 4, 'fire', DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Pro tip for learners%' LIMIT 1), 3, 'clap', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Pro tip for learners%' LIMIT 1), 4, 'like', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Excited to announce%' LIMIT 1), 2, 'fire', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Excited to announce%' LIMIT 1), 3, 'heart', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'We just crossed%' LIMIT 1), 2, 'clap', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'We just crossed%' LIMIT 1), 4, 'like', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Finished grading%' LIMIT 1), 2, 'heart', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Finished grading%' LIMIT 1), 3, 'clap', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Hot take:%' LIMIT 1), 3, 'laugh', DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
((SELECT id FROM community_feed_posts WHERE content LIKE 'Hot take:%' LIMIT 1), 4, 'wow', DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- ─── COMMUNITY GROUPS ───────────────────────────────────────
INSERT INTO community_spaces_groups (name, description, members_count, privacy, created_at, updated_at, owner_id) VALUES
('Symfony Developers TN', 'A community of Symfony developers in Tunisia. Share tips, ask questions, and collaborate on projects.', 3, 'public', DATE_SUB(NOW(), INTERVAL 30 DAY), NOW(), 2),
('TechCorp Engineering', 'Official group for TechCorp team discussions and announcements.', 2, 'private', DATE_SUB(NOW(), INTERVAL 25 DAY), NOW(), 3),
('Cybersecurity & Ethical Hacking', 'Discuss security research, CTFs, bug bounties, and responsible disclosure.', 3, 'public', DATE_SUB(NOW(), INTERVAL 20 DAY), NOW(), 4),
('Freelancers Hub Tunisia', 'Network, share opportunities, and support fellow freelancers in Tunisia.', 2, 'public', DATE_SUB(NOW(), INTERVAL 15 DAY), NOW(), 2),
('Design & UX Community', 'For designers, UX researchers, and anyone passionate about great user experiences.', 2, 'public', DATE_SUB(NOW(), INTERVAL 10 DAY), NOW(), 4);

-- ─── GROUP MEMBERS ──────────────────────────────────────────
INSERT INTO community_spaces_group_members (group_id, user_id, role, joined_at) VALUES
((SELECT id FROM community_spaces_groups WHERE name='Symfony Developers TN'), 2, 'owner', DATE_SUB(NOW(), INTERVAL 30 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Symfony Developers TN'), 3, 'member', DATE_SUB(NOW(), INTERVAL 28 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Symfony Developers TN'), 4, 'member', DATE_SUB(NOW(), INTERVAL 26 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='TechCorp Engineering'), 3, 'owner', DATE_SUB(NOW(), INTERVAL 25 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='TechCorp Engineering'), 2, 'member', DATE_SUB(NOW(), INTERVAL 22 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Cybersecurity & Ethical Hacking'), 4, 'owner', DATE_SUB(NOW(), INTERVAL 20 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Cybersecurity & Ethical Hacking'), 2, 'member', DATE_SUB(NOW(), INTERVAL 18 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Cybersecurity & Ethical Hacking'), 3, 'member', DATE_SUB(NOW(), INTERVAL 16 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Freelancers Hub Tunisia'), 2, 'owner', DATE_SUB(NOW(), INTERVAL 15 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Freelancers Hub Tunisia'), 4, 'member', DATE_SUB(NOW(), INTERVAL 12 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Design & UX Community'), 4, 'owner', DATE_SUB(NOW(), INTERVAL 10 DAY)),
((SELECT id FROM community_spaces_groups WHERE name='Design & UX Community'), 2, 'member', DATE_SUB(NOW(), INTERVAL 8 DAY));

-- ─── COMMUNITY EVENTS ──────────────────────────────────────
INSERT INTO community_spaces_events (title, description, starts_at, location, online_url, rsvps_count, created_at, updated_at, host_id) VALUES
('Tunis Tech Meetup #12', 'Monthly tech meetup covering AI, web dev, and startup culture. Networking + lightning talks.', DATE_ADD(NOW(), INTERVAL 3 DAY), 'Esprit Campus, Tunis', NULL, 8, DATE_SUB(NOW(), INTERVAL 7 DAY), NOW(), 2),
('Symfony Live Workshop', 'Hands-on workshop: Building APIs with Symfony and API Platform. Bring your laptop!', DATE_ADD(NOW(), INTERVAL 10 DAY), NULL, 'https://meet.google.com/abc-defg-hij', 5, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW(), 4),
('Cybersecurity CTF Challenge', '48-hour Capture The Flag competition. Teams of 2-4. Prizes for top 3 teams!', DATE_ADD(NOW(), INTERVAL 14 DAY), 'Online', 'https://ctf.skilora.dev', 12, DATE_SUB(NOW(), INTERVAL 3 DAY), NOW(), 4),
('Freelancer Coffee & Connect', 'Informal meetup for freelancers. Share wins, challenges, and find collaborators.', DATE_ADD(NOW(), INTERVAL 7 DAY), 'Café des Négociants, La Marsa', NULL, 4, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), 2),
('UX Design Sprint Workshop', 'Learn Google''s Design Sprint methodology in a 1-day intensive workshop.', DATE_ADD(NOW(), INTERVAL 21 DAY), NULL, 'https://zoom.us/j/123456789', 6, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), 4);

-- ─── EVENT RSVPs ────────────────────────────────────────────
INSERT INTO community_spaces_event_rsvps (event_id, user_id, created_at) VALUES
((SELECT id FROM community_spaces_events WHERE title='Tunis Tech Meetup #12'), 2, DATE_SUB(NOW(), INTERVAL 6 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Tunis Tech Meetup #12'), 3, DATE_SUB(NOW(), INTERVAL 5 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Tunis Tech Meetup #12'), 4, DATE_SUB(NOW(), INTERVAL 4 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Symfony Live Workshop'), 2, DATE_SUB(NOW(), INTERVAL 4 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Symfony Live Workshop'), 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Cybersecurity CTF Challenge'), 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Cybersecurity CTF Challenge'), 4, DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Freelancer Coffee & Connect'), 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM community_spaces_events WHERE title='Freelancer Coffee & Connect'), 4, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ─── BLOG ARTICLES ──────────────────────────────────────────
INSERT INTO community_blog_articles (title, slug, excerpt, content, status, published_at, created_at, updated_at, author_id) VALUES
('Getting Started with Symfony 7', 'getting-started-with-symfony-7', 'A beginner-friendly guide to building web applications with Symfony 7.', 'Symfony 7 brings exciting new features including improved performance, native type declarations, and a streamlined developer experience.\n\n## Installation\n\nTo get started, install Symfony CLI and create a new project:\n\n```bash\nsymfony new my-project --webapp\n```\n\n## Routing\n\nSymfony uses PHP attributes for routing:\n\n```php\n#[Route(\"/hello\", name: \"app_hello\")]\npublic function hello(): Response { ... }\n```\n\n## Conclusion\n\nSymfony 7 is a fantastic framework for building modern PHP applications. Start experimenting today!', 'published', DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), NOW(), 4),
('Top 10 Security Mistakes Developers Make', 'top-10-security-mistakes', 'Common security pitfalls in web applications and how to avoid them.', 'Security is not optional. Here are the top 10 mistakes I see in code reviews:\n\n1. **Hardcoded secrets** — Use environment variables.\n2. **No input validation** — Always validate at the boundary.\n3. **SQL injection** — Use parameterized queries.\n4. **Missing CSRF protection** — Symfony handles this, but don''t disable it.\n5. **Weak passwords** — Enforce minimum complexity.\n6. **No rate limiting** — Protect login and API endpoints.\n7. **Verbose error messages** — Never expose stack traces in production.\n8. **Missing HTTPS** — Enforce TLS everywhere.\n9. **Outdated dependencies** — Run `composer audit` regularly.\n10. **No logging** — You can''t investigate what you don''t log.\n\nStay safe out there!', 'published', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), NOW(), 2),
('Why I Chose Skilora for Hiring', 'why-i-chose-skilora-for-hiring', 'Our experience using Skilora''s recruitment platform to build our engineering team.', 'When TechCorp Tunisia needed to scale our engineering team, we evaluated several platforms. Here''s why Skilora stood out:\n\n## Quality Candidates\n\nThe ML-powered matching score saved us hours of manual CV screening. We found candidates with 85%+ match scores consistently performed well in interviews.\n\n## Streamlined Process\n\nFrom posting to hiring, the entire workflow — applications, interviews, offers, contracts — lives in one platform.\n\n## Transparent Pricing\n\nNo hidden fees. The escrow system gives both parties confidence.\n\nWe''ve hired 3 developers through Skilora so far and plan to continue.', 'published', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), NOW(), 3),
('Building a Learning Habit: 30-Day Challenge', 'building-a-learning-habit', 'How I committed to 30 minutes of learning every day for a month.', 'Last month I challenged myself to spend at least 30 minutes learning something new every day on Skilora. Here''s what happened:\n\n- **Week 1:** Struggled with consistency. Set phone reminders.\n- **Week 2:** Started enjoying the Python course. Momentum building.\n- **Week 3:** Completed my first quiz with 95%! Confidence boost.\n- **Week 4:** Finished the entire Data Science course and started Cybersecurity.\n\n## Key Takeaway\n\nConsistency beats intensity. 30 minutes daily compounds faster than 5-hour weekend sessions.', 'published', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), 2),
('The Future of Remote Work in Tunisia', 'future-remote-work-tunisia', 'Draft: Exploring the remote work landscape in Tunisia for 2025 and beyond.', 'Tunisia''s tech talent is increasingly working remotely for international companies. This article explores the trends, challenges, and opportunities...', 'draft', NULL, NOW(), NOW(), 3);

-- ─── SUPPORT TICKETS ────────────────────────────────────────
INSERT INTO support_tickets (subject, description, category, priority, status, created_at, updated_at, requester_id, assigned_to_id, resolved_at, feedback_rating, feedback_comment) VALUES
('Cannot upload CV in application form', 'I''m trying to apply for the Senior Developer position but the CV upload keeps failing. I''ve tried PDF and DOCX formats, both under 5MB. Browser: Chrome 120 on macOS.', 'technical', 'high', 'in_progress', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW(), 2, 1, NULL, NULL, NULL),
('Payment not reflected in wallet', 'I topped up my wallet with 500 TND via card payment but the balance hasn''t updated after 30 minutes. Transaction reference: TXN-2024-00123.', 'finance', 'high', 'open', DATE_SUB(NOW(), INTERVAL 3 DAY), NOW(), 3, NULL, NULL, NULL, NULL),
('Request to change username', 'I''d like to change my username from "user" to "ayoub_dev". Is this possible through the platform or does it need admin action?', 'account', 'low', 'resolved', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY), 2, 1, DATE_SUB(NOW(), INTERVAL 8 DAY), 5, 'Quick and helpful!'),
('Course certificate not generating', 'I completed the Python for Data Science course 100% but the certificate download button is grayed out. My enrollment shows as completed.', 'technical', 'normal', 'open', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), 2, NULL, NULL, NULL, NULL),
('How to create a company profile?', 'I want to set up my company profile to post job offers. Can you guide me through the process or point me to documentation?', 'other', 'low', 'closed', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), 3, 1, DATE_SUB(NOW(), INTERVAL 12 DAY), 4, 'Very helpful guidance.'),
('Quiz timer issue - counts too fast', 'During the Web Dev Midterm quiz, the timer seemed to count down faster than real time. I had 30 minutes but it felt like 20. Other students reported the same.', 'technical', 'normal', 'in_progress', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), 2, 1, NULL, NULL, NULL);

-- ─── SUPPORT MESSAGES ───────────────────────────────────────
INSERT INTO support_messages (body, internal_note, created_at, ticket_id, sender_id) VALUES
('I''m trying to apply for the Senior Developer position but the CV upload keeps failing. I''ve tried PDF and DOCX formats, both under 5MB.', 0, DATE_SUB(NOW(), INTERVAL 5 DAY), (SELECT id FROM support_tickets WHERE subject='Cannot upload CV in application form'), 2),
('Hi Ayoub, thanks for reporting this. We''re looking into the file upload issue. Could you try clearing your browser cache and attempting again?', 0, DATE_SUB(NOW(), INTERVAL 4 DAY), (SELECT id FROM support_tickets WHERE subject='Cannot upload CV in application form'), 1),
('Cleared cache and tried again, same error. Here''s the console error: "413 Request Entity Too Large". My file is only 2MB though.', 0, DATE_SUB(NOW(), INTERVAL 4 DAY), (SELECT id FROM support_tickets WHERE subject='Cannot upload CV in application form'), 2),
('Looks like a server-side config issue with nginx. Forwarding to the dev team.', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), (SELECT id FROM support_tickets WHERE subject='Cannot upload CV in application form'), 1),
('I topped up my wallet with 500 TND but the balance hasn''t updated.', 0, DATE_SUB(NOW(), INTERVAL 3 DAY), (SELECT id FROM support_tickets WHERE subject='Payment not reflected in wallet'), 3),
('I''d like to change my username to "ayoub_dev". Is this possible?', 0, DATE_SUB(NOW(), INTERVAL 10 DAY), (SELECT id FROM support_tickets WHERE subject='Request to change username'), 2),
('Hi Ayoub! I''ve updated your username. Please log out and back in for the change to take effect.', 0, DATE_SUB(NOW(), INTERVAL 8 DAY), (SELECT id FROM support_tickets WHERE subject='Request to change username'), 1),
('Thank you, works perfectly!', 0, DATE_SUB(NOW(), INTERVAL 8 DAY), (SELECT id FROM support_tickets WHERE subject='Request to change username'), 2),
('I completed Python for Data Science 100% but the certificate download is grayed out.', 0, DATE_SUB(NOW(), INTERVAL 2 DAY), (SELECT id FROM support_tickets WHERE subject='Course certificate not generating'), 2),
('How to create a company profile to post job offers?', 0, DATE_SUB(NOW(), INTERVAL 15 DAY), (SELECT id FROM support_tickets WHERE subject='How to create a company profile?'), 3),
('Hi! Go to your profile settings > Company tab > Create Company. Once verified you can post job offers. Let me know if you need help!', 0, DATE_SUB(NOW(), INTERVAL 13 DAY), (SELECT id FROM support_tickets WHERE subject='How to create a company profile?'), 1),
('Got it, thanks! Company profile is set up.', 0, DATE_SUB(NOW(), INTERVAL 12 DAY), (SELECT id FROM support_tickets WHERE subject='How to create a company profile?'), 3),
('The quiz timer seems to count down faster than real time during Web Dev Midterm.', 0, DATE_SUB(NOW(), INTERVAL 1 DAY), (SELECT id FROM support_tickets WHERE subject='Quiz timer issue - counts too fast'), 2),
('Thanks for flagging this. We''ll investigate the timer implementation. How many seconds off would you estimate it was?', 0, DATE_SUB(NOW(), INTERVAL 12 HOUR), (SELECT id FROM support_tickets WHERE subject='Quiz timer issue - counts too fast'), 1);

-- ─── APPLICATIONS (user=2 applying to existing jobs) ────────
INSERT INTO applications (job_offer_id, candidate_id, cover_letter, cv_path, match_score, match_reasons, status, applied_at) VALUES
(1, 2, 'I am excited to apply for this position. With my background in full-stack development and recent Symfony certification from Skilora, I believe I would be a strong fit for your team.', NULL, 87, '["Strong Symfony experience","Full-stack skills match","Active Skilora learner"]', 'interview', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(2, 2, 'I bring solid Python and data analysis skills that align perfectly with this role. I have completed the Data Science course on Skilora with a 95% quiz score.', NULL, 72, '["Python proficiency","Data analysis skills","Eager learner"]', 'applied', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(3, 2, 'Passionate about cybersecurity with hands-on CTF experience. Currently completing the Cybersecurity Fundamentals course on Skilora.', NULL, 65, '["Security interest","Technical foundation","Growing skillset"]', 'applied', DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ─── JOB INTERVIEW (for first application) ──────────────────
INSERT INTO job_interviews (application_id, scheduled_at, duration_minutes, format, meeting_provider, meeting_url, notes, status, created_at)
SELECT a.id, DATE_ADD(NOW(), INTERVAL 5 DAY), 45, 'online', 'google_meet', 'https://meet.google.com/xyz-abc-123', 'Technical interview focusing on Symfony and system design. Prepare a 10-min presentation of a recent project.', 'scheduled', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM applications a WHERE a.job_offer_id = 1 AND a.candidate_id = 2 LIMIT 1;

-- ─── NOTIFICATIONS ──────────────────────────────────────────
INSERT INTO notifications (type, title, message, icon, is_read, reference_type, reference_id, created_at, user_id) VALUES
('application', 'Interview Scheduled', 'Your interview for the developer position has been scheduled.', 'calendar', 0, 'interview', 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 2),
('community', 'New Reaction', 'TechCorp Tunisia reacted to your post with 👏', 'heart', 0, 'post', 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 2),
('community', 'New Comment', 'Nadia Formateur commented on your post.', 'message-circle', 0, 'post', 1, DATE_SUB(NOW(), INTERVAL 12 HOUR), 2),
('formation', 'Quiz Available', 'A new quiz is available for Cybersecurity Fundamentals.', 'file-question', 0, 'quiz', 1, DATE_SUB(NOW(), INTERVAL 6 HOUR), 2),
('support', 'Ticket Updated', 'Admin replied to your ticket about CV upload.', 'headphones', 1, 'ticket', 1, DATE_SUB(NOW(), INTERVAL 4 DAY), 2),
('application', 'New Application', 'Ayoub Ben Youssef applied for Senior Developer.', 'user-plus', 0, 'application', 1, DATE_SUB(NOW(), INTERVAL 14 DAY), 3),
('community', 'New Comment', 'Ayoub Ben Youssef commented on your job post.', 'message-circle', 1, 'post', 2, DATE_SUB(NOW(), INTERVAL 2 DAY), 3),
('formation', 'New Review', 'Ayoub Ben Youssef rated Full-Stack Web Development 5 stars.', 'star', 0, 'formation', 1, DATE_SUB(NOW(), INTERVAL 3 DAY), 4),
('formation', 'New Enrollment', 'A new student enrolled in Cybersecurity Fundamentals.', 'user-plus', 0, 'enrollment', 1, DATE_SUB(NOW(), INTERVAL 7 DAY), 4),
('community', 'New Reaction', 'Ayoub Ben Youssef reacted to your cybersecurity announcement.', 'heart', 1, 'post', 4, DATE_SUB(NOW(), INTERVAL 20 HOUR), 4);

SET FOREIGN_KEY_CHECKS = 1;

-- Done! Seeded: formations (6), modules (14), enrollments (3), reviews (2), quizzes (3),
-- quiz questions (7), community posts (8), comments (15), reactions (14), groups (5),
-- group members (12), events (5), event RSVPs (9), blog articles (5), support tickets (6),
-- support messages (14), applications (3), interview (1), notifications (10), wallets (3).
