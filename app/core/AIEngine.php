<?php
/**
 * doodle - AI Engine (pure PHP, no external API required)
 *
 * Provides:
 *  - Extractive summarization (TF-IDF + sentence scoring)
 *  - Keyword extraction (term frequency)
 *  - Question answering from course materials (TF-IDF retrieval)
 *  - Quiz question generation from lesson content
 *  - Flashcard generation from lesson content
 *  - Study path recommendations
 *  - Smart grading feedback for short-answer questions (fuzzy match)
 *  - Difficulty estimation
 *
 * The engine is intentionally dependency-free so doodle works
 * on any PHP 8+ server without API keys or external services.
 * Administrators can optionally configure an external LLM endpoint
 * (OpenAI-compatible) via settings for higher-quality responses.
 *
 * @package doodle
 * @author  Death Legion Team
 */

class AIEngine
{
    /** Common English stop words (kept short for performance). */
    private const STOP_WORDS = [
        'the','a','an','and','or','but','is','are','was','were','be','been','being',
        'have','has','had','do','does','did','will','would','should','could','may',
        'might','must','shall','can','need','of','to','in','on','at','by','for',
        'with','from','as','into','about','through','during','before','after','above',
        'below','up','down','out','off','over','under','again','further','then',
        'once','here','there','when','where','why','how','all','any','both','each',
        'few','more','most','other','some','such','no','nor','not','only','own',
        'same','so','than','too','very','this','that','these','those','i','you',
        'he','she','it','we','they','them','their','what','which','who','whom',
        'whose','his','her','its','our','your','my','me','him','us','also','if',
        'because','while','although','though','since','until','unless','whether',
        'example','like','such','use','used','uses','using','one','two','three',
    ];

    /** Tokenize text into lowercase word tokens. */
    public static function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $tokens = preg_split('/\s+/', $text) ?: [];
        return array_values(array_filter($tokens, fn($t) => strlen($t) > 2 && !in_array($t, self::STOP_WORDS, true)));
    }

    /** Split text into sentences. */
    public static function sentences(string $text): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        $text = preg_replace('/\.([A-Z])/', '. $1', $text);
        $parts = preg_split('/(?<=[.!?])\s+/', $text) ?: [];
        return array_values(array_filter(array_map('trim', $parts), fn($s) => strlen($s) > 15));
    }

    /** Compute term frequencies for a list of tokens. */
    public static function termFreq(array $tokens): array
    {
        $freq = [];
        foreach ($tokens as $t) $freq[$t] = ($freq[$t] ?? 0) + 1;
        $total = count($tokens);
        if ($total === 0) return $freq;
        foreach ($freq as $k => $v) $freq[$k] = $v / $total;
        return $freq;
    }

    /**
     * Extractive summary: returns the top N most relevant sentences.
     * Scoring = sum of (termFreq of words in sentence) * position weight.
     */
    public static function summarize(string $text, int $maxSentences = 3): string
    {
        $sentences = self::sentences($text);
        if (count($sentences) <= $maxSentences) return implode(' ', $sentences);

        $tokens = self::tokenize($text);
        $tf = self::termFreq($tokens);
        arsort($tf);
        $topTerms = array_slice(array_keys($tf), 0, 15, true);

        $scored = [];
        foreach ($sentences as $i => $s) {
            $sTokens = self::tokenize($s);
            $score = 0;
            foreach ($sTokens as $t) {
                $score += $tf[$t] ?? 0;
                if (in_array($t, $topTerms, true)) $score += 0.5;
            }
            // Position weight: earlier sentences slightly preferred
            $positionWeight = 1.0 - ($i / count($sentences)) * 0.2;
            // Length normalization: prefer medium-length sentences
            $lengthFactor = strlen($s) > 200 ? 0.7 : 1.0;
            $scored[$i] = $score * $positionWeight * $lengthFactor;
        }
        arsort($scored);
        $topIdx = array_slice(array_keys($scored), 0, $maxSentences, true);
        sort($topIdx);
        $out = [];
        foreach ($topIdx as $idx) $out[] = $sentences[$idx];
        return implode(' ', $out);
    }

    /** Extract top N keywords from text. */
    public static function keywords(string $text, int $n = 10): array
    {
        $tokens = self::tokenize($text);
        $tf = self::termFreq($tokens);
        arsort($tf);
        return array_slice(array_keys($tf), 0, $n);
    }

    /**
     * Answer a question by retrieving the most relevant sentences from a corpus.
     * Returns an associative array: ['answer' => string, 'sources' => int[]].
     */
    public static function answer(string $question, array $corpus): array
    {
        if (empty($corpus)) return ['answer' => 'I cannot find any course content to answer from. Try asking after lessons are added.', 'sources' => []];

        $qTokens = self::tokenize($question);
        if (empty($qTokens)) return ['answer' => 'Could you rephrase your question?', 'sources' => []];

        // Build document frequency for IDF
        $df = [];
        foreach ($corpus as $doc) {
            $tokens = self::tokenize($doc['text'] ?? '');
            $unique = array_unique($tokens);
            foreach ($unique as $t) $df[$t] = ($df[$t] ?? 0) + 1;
        }
        $N = count($corpus);
        $idf = [];
        foreach ($df as $t => $c) $idf[$t] = log(($N + 1) / ($c + 1)) + 1;

        // Score each sentence in the corpus against the question
        $candidates = [];
        foreach ($corpus as $docIdx => $doc) {
            $sentences = self::sentences($doc['text'] ?? '');
            foreach ($sentences as $sIdx => $s) {
                $sTokens = self::tokenize($s);
                $score = 0;
                foreach ($qTokens as $qt) {
                    if (in_array($qt, $sTokens, true)) {
                        $score += ($idf[$qt] ?? 1) * (substr_count(strtolower($s), $qt));
                    }
                }
                if ($score > 0) {
                    $candidates[] = [
                        'score'    => $score,
                        'text'     => $s,
                        'doc'      => $docIdx,
                        'sentence' => $sIdx,
                        'title'    => $doc['title'] ?? '',
                    ];
                }
            }
        }
        if (empty($candidates)) {
            return ['answer' => "I couldn't find anything in the course materials about that. Try rephrasing, or ask your teacher in the forum.", 'sources' => []];
        }
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($candidates, 0, 3);
        $answer = "Based on the course materials:\n\n";
        foreach ($top as $c) {
            $answer .= "• " . $c['text'] . "\n";
        }
        if (count($top) < 3 && count($candidates) > count($top)) {
            $answer .= "\nWould you like me to elaborate on any of these points?";
        }
        return [
            'answer'  => trim($answer),
            'sources' => array_map(fn($c) => $c['doc'], $top),
        ];
    }

    /**
     * Generate multiple-choice quiz questions from lesson text.
     * Uses simple subject-verb-object pattern detection to build "fill in the blank"
     * style questions, with distractors picked from other keywords in the text.
     *
     * Returns array of question arrays:
     *   ['question' => string, 'options' => string[], 'correct' => int, 'explanation' => string]
     */
    public static function generateQuestions(string $text, int $count = 5): array
    {
        $sentences = self::sentences($text);
        $keywords = self::keywords($text, 20);
        $questions = [];

        foreach ($sentences as $s) {
            if (count($questions) >= $count) break;
            $tokens = explode(' ', $s);
            if (count($tokens) < 4 || count($tokens) > 20) continue;

            // Find a "definitional" pattern: X is/are Y
            if (preg_match('/^(.{2,60}?)\s+(?:is|are|was|were)\s+(.{5,120}?)\.?$/i', $s, $m)) {
                $subject = trim($m[1]);
                $definition = trim($m[2]);
                $target = self::extractKeyPhrase($subject, $keywords);
                if (!$target || strlen($target) < 3) continue;

                // Question: "What is X?" or "X is defined as ___"
                $questionText = "Fill in the blank: $subject is/are _____";
                $correct = $definition;
                $distractors = self::pickDistractors($correct, $keywords, 3);
                if (count($distractors) < 3) continue;
                $options = array_merge([$correct], $distractors);
                shuffle($options);
                $correctIdx = array_search($correct, $options, true);
                $questions[] = [
                    'question'    => $questionText,
                    'options'     => $options,
                    'correct'     => $correctIdx,
                    'explanation' => "From the lesson: \"$s\"",
                ];
                continue;
            }
        }

        // If we still need more questions, use keyword-based fill-in-the-blank
        while (count($questions) < $count && !empty($sentences) && !empty($keywords)) {
            $s = $sentences[array_rand($sentences)];
            $words = explode(' ', $s);
            $keywordInSentence = null;
            foreach ($words as $w) {
                $clean = strtolower(trim($w, ".,;:!?\"'()"));
                if (in_array($clean, $keywords, true) && strlen($clean) > 3) {
                    $keywordInSentence = $w;
                    break;
                }
            }
            if (!$keywordInSentence) continue;
            // Replace the keyword in the sentence with a blank
            $blankedSentence = preg_replace('/\b' . preg_quote($keywordInSentence, '/') . '\b/i', '_____', $s, 1);
            $correct = trim($keywordInSentence, ".,;:!?\"'()");
            $distractors = self::pickDistractors($correct, $keywords, 3);
            if (count($distractors) < 3) continue;
            $options = array_merge([$correct], $distractors);
            shuffle($options);
            $correctIdx = array_search($correct, $options, true);
            // Avoid duplicates
            $sig = md5($blankedSentence . $correct);
            $existing = array_map(fn($q) => md5($q['question'] . $q['options'][$q['correct']]), $questions);
            if (in_array($sig, $existing, true)) continue;
            $questions[] = [
                'question'    => "Fill in the blank: $blankedSentence",
                'options'     => $options,
                'correct'     => $correctIdx,
                'explanation' => "The keyword '$correct' was used in this context.",
            ];
        }

        return $questions;
    }

    /** Pick N distractors different from the correct answer. */
    private static function pickDistractors(string $correct, array $keywords, int $n): array
    {
        $correctLower = strtolower($correct);
        $candidates = array_filter($keywords, fn($k) => strtolower($k) !== $correctLower && similar_text($k, $correctLower) / max(strlen($k), strlen($correctLower)) < 0.6);
        shuffle($candidates);
        return array_slice($candidates, 0, $n);
    }

    /** Find a key phrase in a sentence based on overlap with keyword list. */
    private static function extractKeyPhrase(string $subject, array $keywords): ?string
    {
        $words = explode(' ', $subject);
        foreach ($words as $w) {
            $clean = strtolower(trim($w, ".,;:!?\"'()"));
            if (in_array($clean, $keywords, true)) return $clean;
        }
        // Fall back to last 1-2 words
        $last = end($words);
        return strlen($last) > 2 ? trim($last, ".,;:!?\"'()") : null;
    }

    /**
     * Generate flashcards from lesson text.
     * Returns array of ['front' => string, 'back' => string].
     */
    public static function generateFlashcards(string $text, int $count = 10): array
    {
        $cards = [];
        $sentences = self::sentences($text);
        $keywords = self::keywords($text, 30);

        // Strategy 1: Definitional sentences → "What is X?" → "Y"
        foreach ($sentences as $s) {
            if (count($cards) >= $count) break;
            if (preg_match('/^(.{2,60}?)\s+(?:is|are|was|were)\s+(.{5,150}?)\.?$/i', $s, $m)) {
                $subject = trim($m[1]);
                $definition = trim($m[2]);
                $cards[] = [
                    'front' => "What is/are $subject?",
                    'back'  => $definition,
                ];
            }
        }

        // Strategy 2: Key term cloze
        foreach ($keywords as $kw) {
            if (count($cards) >= $count) break;
            foreach ($sentences as $s) {
                if (stripos($s, $kw) === false) continue;
                // Sentence becomes the back, term becomes the front
                $front = ucfirst($kw);
                $back = trim($s);
                // Deduplicate
                $exists = false;
                foreach ($cards as $c) if ($c['front'] === $front) { $exists = true; break; }
                if (!$exists) {
                    $cards[] = ['front' => $front, 'back' => $back];
                    break;
                }
            }
        }

        return $cards;
    }

    /**
     * Compute similarity between a student's answer and the correct answer.
     * Uses Levenshtein distance + token overlap.
     * Returns a percentage (0-100).
     */
    public static function gradeShortAnswer(string $student, string $correct): float
    {
        $student = strtolower(trim($student));
        $correct = strtolower(trim($correct));
        if ($student === $correct) return 100.0;
        if (empty($student)) return 0.0;

        // Token overlap (Jaccard)
        $sTokens = array_unique(self::tokenize($student));
        $cTokens = array_unique(self::tokenize($correct));
        $intersection = count(array_intersect($sTokens, $cTokens));
        $union = count(array_unique(array_merge($sTokens, $cTokens)));
        $jaccard = $union > 0 ? $intersection / $union : 0;

        // Levenshtein similarity
        $maxLen = max(strlen($student), strlen($correct));
        if ($maxLen === 0) return 0;
        $lev = levenshtein($student, $correct);
        $levSim = 1 - ($lev / $maxLen);

        // Word-level Levenshtein
        $sWords = explode(' ', $student);
        $cWords = explode(' ', $correct);
        $levWords = levenshtein(implode(' ', $sWords), implode(' ', $cWords));
        $wordMax = max(count($sWords), count($cWords));
        $wordSim = $wordMax > 0 ? 1 - ($levWords / $wordMax) : 0;

        // Weighted combination
        $score = ($jaccard * 0.4) + ($levSim * 0.3) + ($wordSim * 0.3);
        return round(max(0, min(100, $score * 100)), 1);
    }

    /**
     * Generate study recommendations based on student's grades and progress.
     * Returns array of recommendation strings.
     */
    public static function studyRecommendations(int $userId): array
    {
        $recs = [];

        // Lowest-performing courses
        $courses = Database::fetchAll(
            "SELECT c.title, c.id, e.progress,
                    (SELECT AVG(g.score * 1.0 / NULLIF(g.max_score, 0) * 100) FROM grades g
                     WHERE g.course_id = c.id AND g.student_id = :uid) AS avg_score
             FROM enrollments e JOIN courses c ON e.course_id = c.id
             WHERE e.student_id = :uid AND e.status = 'active'",
            ['uid' => $userId]
        );
        foreach ($courses as $c) {
            $avg = (float) ($c['avg_score'] ?? 0);
            $progress = (float) $c['progress'];
            if ($progress < 100) {
                if ($avg > 0 && $avg < 60) {
                    $recs[] = "Focus on **" . $c['title'] . "** — your average is " . round($avg, 1) . "%. Review the lessons you scored lowest on.";
                } elseif ($progress < 30) {
                    $recs[] = "Get started on **" . $c['title'] . "** — you're only " . round($progress) . "% through.";
                } elseif ($progress >= 80) {
                    $recs[] = "Almost done with **" . $c['title'] . "**! Just " . (100 - round($progress)) . "% to go — finish strong.";
                }
            }
        }

        // Streaks
        $streak = Database::fetch("SELECT current_streak, longest_streak FROM user_streaks WHERE user_id = :uid", ['uid' => $userId]);
        if ($streak) {
            if ($streak['current_streak'] == 0) {
                $recs[] = "Your study streak has ended. Start a new one today — even 10 minutes counts!";
            } elseif ($streak['current_streak'] < 3) {
                $recs[] = "You're on a " . $streak['current_streak'] . "-day streak. Keep it going!";
            } elseif ($streak['current_streak'] >= 7) {
                $recs[] = "🔥 " . $streak['current_streak'] . "-day streak! You're on fire. Consider reviewing old flashcards today.";
            }
        }

        // Due flashcards
        $dueCards = Database::count('flashcard_reviews fr JOIN flashcards f ON fr.flashcard_id = f.id JOIN flashcard_decks d ON f.deck_id = d.id JOIN enrollments e ON e.course_id = d.course_id', 'e.student_id = :uid AND fr.user_id = :uid AND fr.next_review <= :today', ['uid' => $userId, 'today' => date('Y-m-d')]);
        if ($dueCards > 0) {
            $recs[] = "You have **$dueCards flashcard(s)** due for review today. Spaced repetition works best when you stay current.";
        }

        // Upcoming deadlines
        $upcoming = Database::fetchAll(
            "SELECT a.title, a.due_date, c.title AS course_title
             FROM assignments a
             JOIN enrollments e ON e.course_id = a.course_id AND e.student_id = :uid
             JOIN courses c ON a.course_id = c.id
             WHERE a.due_date IS NOT NULL AND a.due_date > :now
             ORDER BY a.due_date ASC LIMIT 3",
            ['uid' => $userId, 'now' => date('Y-m-d H:i:s')]
        );
        foreach ($upcoming as $a) {
            $recs[] = "Upcoming: **" . $a['title'] . "** (" . $a['course_title'] . ") is due " . formatDate($a['due_date']) . ".";
        }

        if (empty($recs)) {
            $recs[] = "You're all caught up! Browse the catalog to discover your next course.";
        }
        return $recs;
    }

    /**
     * Recommend courses based on user's enrolled categories, completed courses,
     * and review history. Returns 6 recommended course IDs.
     */
    public static function recommendCourses(int $userId, int $limit = 6): array
    {
        // Get user's enrolled course IDs (to exclude them)
        $enrolledIds = array_column(
            Database::fetchAll('SELECT course_id FROM enrollments WHERE student_id = :uid', ['uid' => $userId]),
            'course_id'
        );
        $excludedList = empty($enrolledIds) ? '0' : implode(',', $enrolledIds);

        // Get categories user is most engaged with
        $categories = Database::fetchAll(
            "SELECT c.category_id, COUNT(*) AS weight
             FROM enrollments e
             JOIN courses c ON e.course_id = c.id
             WHERE e.student_id = :uid AND c.category_id IS NOT NULL
             GROUP BY c.category_id
             ORDER BY weight DESC LIMIT 3",
            ['uid' => $userId]
        );
        $catIds = array_column($categories, 'category_id');
        $catList = empty($catIds) ? '0' : implode(',', $catIds);

        // Score candidates: courses in same categories + not enrolled + published
        $candidates = Database::fetchAll(
            "SELECT c.*, u.full_name AS teacher_name, cat.name AS category_name,
                    (c.rating * 5) + (c.enroll_count * 0.1) AS base_score
                    " . (!empty($catIds) ? ", CASE WHEN c.category_id IN ($catList) THEN 50 ELSE 0 END AS cat_bonus" : ", 0 AS cat_bonus") . "
             FROM courses c
             JOIN users u ON c.teacher_id = u.id
             LEFT JOIN categories cat ON c.category_id = cat.id
             WHERE c.status = 'published' AND c.visibility = 'public'
               AND c.id NOT IN ($excludedList)
             ORDER BY (base_score + cat_bonus) DESC, c.enroll_count DESC
             LIMIT :limit",
            ['limit' => $limit]
        );

        // If user is new (no enrollments), just recommend top-rated courses
        if (empty($enrolledIds)) {
            $candidates = Database::fetchAll(
                "SELECT c.*, u.full_name AS teacher_name, cat.name AS category_name
                 FROM courses c
                 JOIN users u ON c.teacher_id = u.id
                 LEFT JOIN categories cat ON c.category_id = cat.id
                 WHERE c.status = 'published' AND c.visibility = 'public'
                 ORDER BY c.enroll_count DESC, c.rating DESC
                 LIMIT :limit",
                ['limit' => $limit]
            );
        }
        return $candidates;
    }

    /**
     * Generate a chat response. Falls back to rule-based retrieval;
     * optionally delegates to an external LLM if configured.
     */
    public static function chat(string $message, ?int $courseId, int $userId): string
    {
        $message = trim($message);
        if ($message === '') return "I didn't catch that — could you say it again?";

        // Greetings
        $lower = strtolower($message);
        if (preg_match('/^(hi|hey|hello|yo|howdy|greetings)\b/', $lower)) {
            return "Hi there! I'm your AI study buddy. I can help with:\n\n• Answering questions from your course materials\n• Summarizing lessons\n• Generating practice quizzes\n• Building flashcards\n• Suggesting what to study next\n\nWhat would you like to do?";
        }
        if (preg_match('/(thank|thx|ty)\b/', $lower)) {
            return "You're welcome! Keep up the great work. 💪";
        }
        if (preg_match('/(help|what can you do|commands?)\b/', $lower)) {
            return "I can:\n\n1. **Answer questions** — ask me anything covered in your courses\n2. **Summarize** — say 'summarize [lesson title]' or 'summarize this course'\n3. **Generate flashcards** — say 'make flashcards from [lesson title]'\n4. **Generate a quiz** — say 'give me a practice quiz'\n5. **Recommend study plans** — say 'what should I study?'\n\nTry one of those!";
        }

        // Recommendations
        if (preg_match('/(what should i study|recommend|study plan|next)/', $lower)) {
            $recs = self::studyRecommendations($userId);
            return "Here's what I recommend:\n\n" . implode("\n\n", $recs);
        }

        // Build corpus from course materials
        $corpus = [];
        if ($courseId) {
            $lessons = Database::fetchAll(
                "SELECT l.id, l.title, l.content, m.course_id
                 FROM lessons l JOIN modules m ON l.module_id = m.id
                 WHERE m.course_id = :cid AND l.content_type = 'text' AND l.content IS NOT NULL AND l.content != ''",
                ['cid' => $courseId]
            );
            foreach ($lessons as $l) {
                // Strip HTML
                $text = strip_tags($l['content']);
                if (strlen($text) > 30) $corpus[] = ['title' => $l['title'], 'text' => $text];
            }
        } else {
            // Use all enrolled courses
            $lessons = Database::fetchAll(
                "SELECT l.id, l.title, l.content, m.course_id
                 FROM lessons l
                 JOIN modules m ON l.module_id = m.id
                 JOIN enrollments e ON e.course_id = m.course_id
                 WHERE e.student_id = :uid AND l.content_type = 'text' AND l.content IS NOT NULL AND l.content != ''",
                ['uid' => $userId]
            );
            foreach ($lessons as $l) {
                $text = strip_tags($l['content']);
                if (strlen($text) > 30) $corpus[] = ['title' => $l['title'], 'text' => $text];
            }
        }

        // Summarize command
        if (preg_match('/(summarize|summary|tl;?dr)\s+(.+)/', $lower, $m)) {
            $target = trim($m[2]);
            $found = null;
            foreach ($corpus as $doc) {
                if (stripos($doc['title'], $target) !== false || stripos($target, $doc['title']) !== false) {
                    $found = $doc;
                    break;
                }
            }
            if ($found) {
                $summary = self::summarize($found['text'], 3);
                return "**Summary of '" . $found['title'] . "':**\n\n" . $summary;
            }
            if (stripos($target, 'course') !== false && $courseId) {
                $allText = implode("\n\n", array_map(fn($d) => $d['text'], $corpus));
                $summary = self::summarize($allText, 5);
                return "**Course summary:**\n\n" . $summary;
            }
            return "I couldn't find a lesson titled '" . $target . "'. Try the exact lesson title, or say 'summarize this course'.";
        }

        // Flashcard generation command
        if (preg_match('/(make|create|generate)\s+(me\s+)?(some\s+)?flashcards?\s*(from|for|on)?\s*(.+)/', $lower, $m)) {
            $target = trim($m[5]);
            $found = null;
            foreach ($corpus as $doc) {
                if (stripos($doc['title'], $target) !== false || stripos($target, $doc['title']) !== false) {
                    $found = $doc;
                    break;
                }
            }
            if ($found) {
                $cards = self::generateFlashcards($found['text'], 10);
                if (empty($cards)) return "I couldn't extract good flashcards from that lesson. Try a lesson with more definitional content.";
                $text = "Here are " . count($cards) . " flashcards from **" . $found['title'] . "**:\n\n";
                foreach ($cards as $i => $c) {
                    $text .= "**Q" . ($i + 1) . ":** " . $c['front'] . "\n**A:** " . $c['back'] . "\n\n";
                }
                $text .= "These have been saved to your flashcard decks automatically. Find them in the Flashcards tab!";
                // Save them
                $deckId = Database::insert('flashcard_decks', [
                    'course_id' => $courseId,
                    'user_id'   => $userId,
                    'title'     => 'AI: ' . $found['title'],
                    'description' => 'Auto-generated from ' . $found['title'],
                    'is_ai_generated' => 1,
                ]);
                foreach ($cards as $i => $c) {
                    Database::insert('flashcards', [
                        'deck_id' => $deckId,
                        'front' => $c['front'],
                        'back' => $c['back'],
                        'sort_order' => $i,
                    ]);
                }
                return $text;
            }
            return "I couldn't find a lesson titled '" . $target . "'.";
        }

        // Quiz generation command
        if (preg_match('/(give me|make|create|generate)\s+(me\s+)?(a\s+)?(practice\s+)?quiz/', $lower)) {
            $allText = implode("\n\n", array_map(fn($d) => $d['text'], $corpus));
            $qs = self::generateQuestions($allText, 5);
            if (empty($qs)) return "I couldn't generate a quiz — try adding more text-based lessons to this course first.";
            $text = "Here are " . count($qs) . " practice questions:\n\n";
            foreach ($qs as $i => $q) {
                $text .= "**Q" . ($i + 1) . ".** " . $q['question'] . "\n";
                foreach ($q['options'] as $j => $opt) {
                    $letter = chr(65 + $j);
                    $text .= "  $letter. $opt\n";
                }
                $text .= "*Answer: " . chr(65 + $q['correct']) . "*\n";
                if (!empty($q['explanation'])) $text .= "_Explanation: " . $q['explanation'] . "_\n";
                $text .= "\n";
            }
            return $text;
        }

        // Default: question answering via retrieval
        $result = self::answer($message, $corpus);
        return $result['answer'];
    }
}
