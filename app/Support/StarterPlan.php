<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Household;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;

class StarterPlan
{
    public function ensureFor(User $user): void
    {
        if ($user->hasRole(UserRole::Student, UserRole::Evaluator)) {
            return;
        }

        $household = $this->ensureHousehold($user);

        if ($household->students()->exists()) {
            return;
        }

        $younger = $household->students()->create([
            'user_id' => $user->id,
            'name' => 'Maty',
            'age' => 12,
            'level' => 'Middle school bridge',
            'target_grad_year' => '2031 or 2032',
            'weekly_capacity_hours' => 18,
            'learning_style' => 'Short lessons, concrete projects, visible progress, and frequent narration before long written work.',
            'strengths' => 'Curiosity, pattern finding, practical problem solving.',
            'friction' => 'Long open-ended writing and vague assignments need scaffolding.',
            'college_direction' => 'Explore strengths now; build math confidence, writing fluency, and durable study habits.',
            'interests' => ['engineering', 'stories', 'outdoors', 'strategy games'],
        ]);

        $older = $household->students()->create([
            'user_id' => $user->id,
            'name' => 'Tor',
            'age' => 15,
            'level' => 'High school planning year',
            'target_grad_year' => '2029 or 2030',
            'weekly_capacity_hours' => 24,
            'learning_style' => 'Seminar conversations, independent blocks, clear rubrics, and serious projects with outside audiences.',
            'strengths' => 'Abstract thinking, independence, debate, and deep dives when the topic matters.',
            'friction' => 'Consistency, documentation, and turning big interests into finished evidence.',
            'college_direction' => 'Build a credible high school transcript plus a distinctive project portfolio.',
            'interests' => ['computer science', 'history', 'entrepreneurship', 'film'],
        ]);

        $youngerCourses = [
            [
                'title' => 'Algebra Readiness Lab',
                'subject' => 'Math',
                'credit_goal' => 0,
                'weekly_hours' => 4,
                'level' => 'foundation',
                'why' => 'Build the pre-algebra to algebra bridge before high school credits start to matter.',
                'skills' => ['fractions', 'ratios', 'linear patterns', 'math narration'],
                'outputs' => ['weekly problem notebook', 'teach-back videos', 'mastery checks'],
            ],
            [
                'title' => 'Stories, History, and Composition',
                'subject' => 'English',
                'credit_goal' => 0,
                'weekly_hours' => 5,
                'level' => 'foundation',
                'why' => 'Use high-interest reading and history to build discussion, vocabulary, and paragraph craft.',
                'skills' => ['close reading', 'summary', 'paragraph structure', 'timeline thinking'],
                'outputs' => ['commonplace book', 'monthly essay', 'oral narration log'],
            ],
            [
                'title' => 'Logic, Coding, and Design Studio',
                'subject' => 'Computer Science',
                'credit_goal' => 0,
                'weekly_hours' => 3,
                'level' => 'foundation',
                'why' => 'Turn curiosity into completed artifacts while practicing planning and debugging.',
                'skills' => ['logic', 'sequencing', 'debugging', 'presentation'],
                'outputs' => ['small game prototype', 'design journal', 'demo day'],
            ],
        ];

        $olderCourses = [
            [
                'title' => 'English 9',
                'subject' => 'English',
                'credit_goal' => 1,
                'weekly_hours' => 4,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'A literature and composition spine with enough reading, writing, and revision evidence for a real high school English credit.',
                'skills' => ['literary analysis', 'argument', 'revision', 'research citation'],
                'outputs' => ['reading log', 'four polished essays', 'seminar notes', 'portfolio reflection'],
            ],
            [
                'title' => 'Algebra I',
                'subject' => 'Math',
                'credit_goal' => 1,
                'weekly_hours' => 5,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'Keep the college-prep math sequence honest with visible practice, corrections, and mastery checks.',
                'skills' => ['proportional relationships', 'linear equations', 'functions', 'test corrections'],
                'outputs' => ['unit mastery checks', 'correction journal', 'worked example notebook'],
            ],
            [
                'title' => 'Physical Science',
                'subject' => 'Science',
                'credit_goal' => 1,
                'weekly_hours' => 4,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'Create a lab-science credit with documented simulations, short labs, and topic checks.',
                'skills' => ['measurement', 'experimental design', 'matter', 'energy', 'lab reporting'],
                'outputs' => ['lab notebook', 'simulation notes', 'unit checks', 'research explainer'],
            ],
            [
                'title' => 'World History',
                'subject' => 'Social Studies',
                'credit_goal' => 1,
                'weekly_hours' => 3,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'A 1.0 credit world history course using OpenStax, Crash Course, hand-drawn mind maps, and weekly summary sheets.',
                'skills' => ['timeline synthesis', 'source mapping', 'cultural literacy', 'discussion'],
                'outputs' => ['weekly summary sheets', 'paper mind maps', 'discussion notes', 'unit projects'],
            ],
            [
                'title' => 'German 1',
                'subject' => 'World Language',
                'credit_goal' => 1,
                'weekly_hours' => 3,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'Start the sustained language sequence early enough to matter.',
                'skills' => ['listening', 'speaking', 'reading', 'writing'],
                'outputs' => ['practice log', 'oral recordings', 'unit assessments'],
            ],
            [
                'title' => 'Technology',
                'subject' => 'Elective',
                'credit_goal' => 0.5,
                'weekly_hours' => 3,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'Build technical confidence through web fundamentals and documented projects.',
                'skills' => ['HTML', 'CSS', 'JavaScript', 'debugging'],
                'outputs' => ['small web projects', 'code notes', 'portfolio screenshots'],
            ],
            [
                'title' => 'Art',
                'subject' => 'Arts',
                'credit_goal' => 0.5,
                'weekly_hours' => 2,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'Use practical technique demos and critiques to build a half-credit art portfolio.',
                'skills' => ['drawing fundamentals', 'critique', 'composition', 'media practice'],
                'outputs' => ['sketchbook samples', 'finished pieces', 'photo portfolio'],
            ],
            [
                'title' => 'PE',
                'subject' => 'Wellness',
                'credit_goal' => 1,
                'weekly_hours' => 3,
                'level' => 'standard',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'Make physical education visible with attendance, effort, and skill logs.',
                'skills' => ['conditioning', 'coordination', 'discipline', 'reflection'],
                'outputs' => ['activity log', 'skill reflection', 'attendance notes'],
            ],
            [
                'title' => 'Personal Project',
                'subject' => 'Elective',
                'credit_goal' => 0.5,
                'weekly_hours' => 2,
                'level' => 'honors',
                'school_year' => '2025-26',
                'grade_level' => '9th',
                'why' => 'Turn one serious interest into a finished artifact with planning notes and evidence.',
                'skills' => ['project planning', 'research', 'iteration', 'presentation'],
                'outputs' => ['proposal', 'milestone log', 'final artifact', 'reflection'],
            ],
        ];

        $this->createCourses($younger, $youngerCourses);
        $this->createCourses($older, $olderCourses);
        $this->createAssignments($younger);
        $this->createAssignments($older);
        $this->createCourseHubData($older);
    }

    /**
     * @param  array<int, array<string, mixed>>  $courses
     */
    private function createCourses(Student $student, array $courses): void
    {
        collect($courses)->each(function (array $course) use ($student): void {
            $created = $student->courses()->create([
                'status' => 'active',
                'resources' => [],
                ...$course,
            ]);

            $this->createSyllabus($created);
        });
    }

    private function createSyllabus(Course $course): void
    {
        $course->syllabus()->create([
            'title' => "{$course->title} Syllabus",
            'overview' => $course->why,
            'learning_goals' => collect($course->skills ?? [])
                ->map(fn (string $skill): string => "- {$skill}")
                ->implode("\n"),
            'materials' => 'See the class reference list. Add texts, websites, videos, labs, and paper mind-map sources as they are chosen.',
            'weekly_rhythm' => 'Plan the week, complete daily assignment cards, log reading or discussion evidence, then close with a short reflection.',
            'assessment_plan' => collect($course->outputs ?? [])
                ->map(fn (string $output): string => "- {$output}")
                ->implode("\n"),
            'grading_scale' => $course->credit_goal > 0
                ? 'Parent-evaluated using assignment completion, discussion quality, written evidence, projects, tests, and final portfolio reflection.'
                : 'Foundation course: track progress, evidence, and habits without transcript credit unless promoted later.',
            'evaluator_notes' => 'Use the weekly outline, assignments, reading logs, course logs, grades, work samples, and student reflection as the evaluator packet.',
            'transcript_summary' => $course->credit_goal > 0
                ? "{$course->title}: {$course->credit_goal} credit(s), {$course->subject}, {$course->grade_level} grade level."
                : "{$course->title}: non-credit foundation study in {$course->subject}.",
        ]);
    }

    private function createAssignments(Student $student): void
    {
        $courses = $student->courses()->get()->keyBy('title');
        $today = Carbon::today();

        $assignments = $student->age < 14
            ? [
                ['Algebra Readiness Lab', 'Ratio warmup and teach-back', 'Complete five ratio problems, correct misses, then explain one problem out loud.', Assignment::STATUS_ASSIGNED, 0, 50, 'Practice'],
                ['Stories, History, and Composition', 'Read, narrate, draft', 'Read the assigned pages, narrate the scene, and draft one strong paragraph.', Assignment::STATUS_ASSIGNED, 1, 60, 'Reading'],
                ['Logic, Coding, and Design Studio', 'Paper prototype one game rule', 'Sketch three possible rules, test one, and write what changed.', Assignment::STATUS_ASSIGNED, 0, 60, 'Project'],
            ]
            : [
                ['PE', 'Teens Karate', 'Log attendance, warmup, focus skill, and one sentence of reflection.', Assignment::STATUS_DONE, 0, 60, 'Activity'],
                ['Algebra I', 'Writing and Solving Proportions', 'Khan Academy practice set plus correction notes for missed items.', Assignment::STATUS_DONE, 1, 45, 'Practice'],
                ['Algebra I', 'Unit Test: Proportional Relationships', 'Complete the proportional relationships unit test and record corrections.', Assignment::STATUS_IN_PROGRESS, 0, 75, 'Unit Test'],
                ['World History', 'Week 1: Early Humans and the Neolithic Revolution', 'Read OpenStax, watch the assigned Crash Course segment, and create the summary sheet.', Assignment::STATUS_ASSIGNED, 0, 60, 'Reading'],
                ['World History', 'Paper mind map: Why agriculture changed everything', 'Draw the mind map by hand, then discuss the strongest three connections.', Assignment::STATUS_ASSIGNED, 0, 50, 'Discussion'],
                ['English 9', 'Commonplace notes: The Odyssey', 'Read, choose two passages, and write why each one matters.', Assignment::STATUS_ASSIGNED, 1, 60, 'Reading'],
                ['Technology', 'JavaScript notes: functions and events', 'Build a small button interaction and document what each function does.', Assignment::STATUS_ASSIGNED, 1, 75, 'Project'],
            ];

        foreach ($assignments as [$courseTitle, $title, $description, $status, $position, $minutes, $type]) {
            Assignment::create([
                'student_id' => $student->id,
                'course_id' => $courses->get($courseTitle)?->id,
                'title' => $title,
                'description' => $description,
                'due_date' => $today,
                'estimate_minutes' => $minutes,
                'status' => $status,
                'position' => $position,
                'assignment_type' => $type,
                'max_score' => in_array($type, ['Unit Test', 'Project'], true) ? 100 : null,
                'priority' => $status === Assignment::STATUS_IN_PROGRESS ? 'high' : 'normal',
                'completed_at' => $status === Assignment::STATUS_DONE ? $today : null,
            ]);
        }
    }

    private function createCourseHubData(Student $student): void
    {
        $courses = $student->courses()->get()->keyBy('title');
        $today = Carbon::today();

        $resources = [
            'Art' => [
                ['Art Prof (YouTube)', 'Video Series', 'Free', 'https://www.youtube.com/@ArtProf', 'Technique demos, critiques, and art education.'],
                ['Smarthistory', 'Website', 'Free', 'https://smarthistory.org/', 'Primary art history reference.'],
                ['Proko (YouTube)', 'Video Series', 'Free', 'https://www.youtube.com/@ProkoTV', 'Drawing fundamentals and figure drawing.'],
            ],
            'Technology' => [
                ['freeCodeCamp JavaScript Curriculum', 'Website', 'Free', 'https://www.freecodecamp.org/learn/javascript-algorithms-and-data-structures-v8/', 'Primary adapted JavaScript path.'],
                ['MDN Web Docs', 'Website', 'Free', 'https://developer.mozilla.org/', 'Reference docs for web APIs and JavaScript.'],
                ['Web Dev Simplified (YouTube)', 'Video Series', 'Free', 'https://www.youtube.com/@WebDevSimplified', 'Short, clear JavaScript tutorials.'],
            ],
            'English 9' => [
                ['The Odyssey (Fagles translation)', 'Textbook', '~$12', 'https://www.amazon.com/', 'Semester 1 anchor text.'],
                ['To Kill a Mockingbird', 'Textbook', '~$8', 'https://www.amazon.com/', 'Semester 1 novel study.'],
                ['Animal Farm', 'Textbook', '~$8', 'https://www.amazon.com/', 'Semester 2 political allegory.'],
                ['Romeo and Juliet (Folger Shakespeare)', 'Textbook', '~$5', 'https://www.amazon.com/', 'Semester 2 drama study.'],
            ],
            'Physical Science' => [
                ['CK-12 Physical Science', 'Textbook', 'Free', 'https://www.ck12.org/book/ck-12-physical-science-for-middle-school/', 'Primary reading spine.'],
                ['PhET Interactive Simulations', 'Website', 'Free', 'https://phet.colorado.edu/', 'Virtual labs and simulations.'],
                ['Crash Course Chemistry', 'Video Series', 'Free', 'https://thecrashcourse.com/topic/chemistry/', 'Video support for chemistry units.'],
                ['Khan Academy Science', 'Website', 'Free', 'https://www.khanacademy.org/science', 'Supplemental topic practice.'],
            ],
            'World History' => [
                ['OpenStax World History Volume 1', 'Textbook', 'Free', 'https://openstax.org/details/books/world-history-volume-1', 'Primary reading spine for Semester 1.'],
                ['OpenStax World History Volume 2', 'Textbook', 'Free', 'https://openstax.org/details/books/world-history-volume-2', 'Primary reading spine for Semester 2.'],
                ['Crash Course World History', 'Video Series', 'Free', 'https://thecrashcourse.com/topic/worldhistory1/', 'Primary video source.'],
                ['World History Encyclopedia', 'Website', 'Free', 'https://www.worldhistory.org/', 'Supplemental reading when concepts need reinforcement.'],
            ],
            'Algebra I' => [
                ['Khan Academy Algebra I', 'Website', 'Free', 'https://www.khanacademy.org/math/algebra', 'Practice sets, instructional videos, and checks.'],
            ],
        ];

        foreach ($resources as $courseTitle => $items) {
            $course = $courses->get($courseTitle);

            if (! $course) {
                continue;
            }

            foreach ($items as $position => [$title, $type, $cost, $url, $notes]) {
                $course->resourceLinks()->create([
                    'title' => $title,
                    'resource_type' => $type,
                    'cost' => $cost,
                    'url' => $url,
                    'notes' => $notes,
                    'position' => $position,
                ]);
            }
        }

        $algebra = $courses->get('Algebra I');

        if ($algebra) {
            $algebra->readingLogs()->createMany([
                [
                    'title' => 'Writing and Solving Proportions',
                    'author' => 'Khan Academy',
                    'date_started' => $today->copy()->subDay(),
                    'date_finished' => $today->copy()->subDay(),
                    'status' => 'finished',
                ],
                [
                    'title' => 'Equations of Proportional Relationships',
                    'author' => 'Khan Academy',
                    'date_started' => $today,
                    'date_finished' => null,
                    'status' => 'started',
                ],
            ]);
        }

        $worldHistory = $courses->get('World History');

        if ($worldHistory) {
            $worldHistory->outlineWeeks()->createMany($this->worldHistoryWeeks());
            $weekOne = $worldHistory->outlineWeeks()->where('week_number', 1)->first();

            if ($weekOne) {
                $worldHistory->assignments()
                    ->whereIn('title', [
                        'Week 1: Early Humans and the Neolithic Revolution',
                        'Paper mind map: Why agriculture changed everything',
                    ])
                    ->update(['course_week_id' => $weekOne->id]);
            }

            $worldHistory->readingLogs()->create([
                'course_week_id' => $weekOne?->id,
                'title' => 'Week 1 - Early Humans and the Neolithic Revolution',
                'author' => 'OpenStax and Crash Course',
                'date_started' => $today,
                'status' => 'started',
                'notes' => 'Summary sheet includes key concepts, vocabulary, discussion seed, and source map.',
            ]);

            $worldHistory->courseLogs()->createMany([
                [
                    'course_week_id' => $weekOne?->id,
                    'logged_on' => $today,
                    'log_type' => 'note',
                    'title' => 'Course rhythm imported',
                    'minutes' => null,
                    'body' => 'Three days per week: read, discuss with paper mind map, then project. Two 25-minute Pomodoros per day.',
                ],
                [
                    'course_week_id' => $weekOne?->id,
                    'logged_on' => $today,
                    'log_type' => 'discussion',
                    'title' => 'Week 1 planning',
                    'minutes' => 30,
                    'body' => 'Focus on early humans, the Neolithic Revolution, agriculture, settlement, specialization, and trade.',
                ],
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function worldHistoryWeeks(): array
    {
        $weeks = [
            1 => 'Early Humans and the Neolithic Revolution',
            2 => 'Agriculture, Settlements, and Specialization',
            3 => 'River Valley Civilizations',
            4 => 'Foundations of Civilization Synthesis',
            5 => 'Classical Greece',
            6 => 'The Roman Republic and Empire',
            7 => 'Classical India',
            8 => 'Classical China',
            9 => 'Classical Civilizations Comparison',
            10 => 'Judaism and the Ancient Near East',
            11 => 'Hinduism and Buddhism',
            12 => 'Christianity and the Roman World',
            13 => 'Islam and Expanding Trade Networks',
            14 => 'Byzantine Empire',
            15 => 'Medieval Europe',
            16 => 'African Kingdoms and Trade',
            17 => 'The Islamic World',
            18 => 'Medieval World Synthesis',
            19 => 'Renaissance Humanism',
            20 => 'The Reformation',
            21 => 'Scientific Revolution',
            22 => 'Renaissance and Reformation Project',
            23 => 'Age of Exploration',
            24 => 'Columbian Exchange',
            25 => 'Global Trade and Colonization',
            26 => 'Exchange and Consequence Discussion',
            27 => 'The Enlightenment',
            28 => 'American and French Revolutions',
            29 => 'Latin American Revolutions',
            30 => 'Nationalism',
            31 => 'Revolutions Synthesis',
            32 => 'Industrialization',
            33 => 'Imperialism',
            34 => 'Industrialization and Imperialism Project',
            35 => 'World War I and Interwar Instability',
            36 => 'World War II and the Cold War',
            37 => 'Modern World Portfolio Reflection',
        ];

        return collect($weeks)
            ->map(fn (string $topic, int $week): array => [
                'week_number' => $week,
                'title' => $topic,
                'focus' => "Key concepts: {$topic}; cause and effect; continuity and change; timeline placement.",
                'readings' => "OpenStax World History reading aligned to {$topic}. Add World History Encyclopedia support when the concepts need another angle.",
                'videos' => 'Crash Course World History aligned segment. Day 2 uses a hand-drawn paper mind map and live discussion.',
                'project' => 'Weekly summary sheet: key concepts, vocabulary and cultural literacy, one discussion seed, and exact source map.',
            ])
            ->values()
            ->all();
    }

    private function ensureHousehold(User $user): Household
    {
        if ($user->household) {
            return $user->household;
        }

        $household = Household::create([
            'name' => config('homeschool.starter_household_name'),
        ]);

        $user->forceFill([
            'household_id' => $household->id,
            'role' => $user->role ?: UserRole::Owner->value,
        ])->save();

        $user->setRelation('household', $household);

        return $household;
    }
}
