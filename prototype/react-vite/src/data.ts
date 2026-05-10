import type {
  AppState,
  Course,
  CourseStatus,
  InterviewAnswer,
  LearningModule,
  LearningSession,
  SchoolProfile,
  Student,
  SubjectArea,
} from './types'

export const STORAGE_KEY = 'northstar-homeschool-plan-v1'

export const subjectAreas: SubjectArea[] = [
  'English',
  'Math',
  'Science',
  'Social Studies',
  'World Language',
  'Arts',
  'Computer Science',
  'Wellness',
  'Elective',
]

export const subjectTargets: Array<{
  subject: SubjectArea
  minimum: number
  preferred: number
  note: string
}> = [
  {
    subject: 'English',
    minimum: 4,
    preferred: 4,
    note: 'Four years of reading, writing, rhetoric, and literature.',
  },
  {
    subject: 'Math',
    minimum: 3,
    preferred: 4,
    note: 'Keep the path moving toward algebra, geometry, algebra II, and beyond.',
  },
  {
    subject: 'Science',
    minimum: 3,
    preferred: 4,
    note: 'Include lab evidence for high school science courses.',
  },
  {
    subject: 'Social Studies',
    minimum: 3,
    preferred: 4,
    note: 'History, civics, economics, geography, and primary-source work.',
  },
  {
    subject: 'World Language',
    minimum: 2,
    preferred: 3,
    note: 'Most college-bound plans need at least two years in one language.',
  },
  {
    subject: 'Arts',
    minimum: 1,
    preferred: 1,
    note: 'Visual, performing, design, or music study with portfolio evidence.',
  },
  {
    subject: 'Elective',
    minimum: 2,
    preferred: 4,
    note: 'Use electives to show direction, depth, and real projects.',
  },
]

export const planningSources = [
  {
    title: 'College Board BigFuture course planning',
    url: 'https://bigfuture.collegeboard.org/plan-for-college/stand-out-in-high-school/high-school-classes-colleges-look-for',
    note: 'Core college-prep areas and the reminder to verify requirements against target colleges.',
  },
  {
    title: 'University of California A-G subject pattern',
    url: 'https://admission.universityofcalifornia.edu/admission-requirements/first-year-requirements/subject-requirement-a-g.html',
    note: 'A rigorous, concrete subject checklist that is useful even outside California.',
  },
  {
    title: 'Common App counselor and recommender resources',
    url: 'https://www.commonapp.org/counselors-and-recommenders/',
    note: 'School forms, transcripts, recommendations, and profile context are part of the application machinery.',
  },
  {
    title: 'NCAA homeschool student guidance',
    url: 'https://www.ncaa.org/sports/2014/10/13/home-school-students.aspx',
    note: 'Keep a separate early watch if either student may pursue college athletics.',
  },
]

export const statusLabels: Record<CourseStatus, string> = {
  planned: 'Planned',
  active: 'Active',
  complete: 'Complete',
  paused: 'Paused',
}

const mondayOfCurrentWeek = () => {
  const date = new Date()
  const day = date.getDay()
  const offset = day === 0 ? -6 : 1 - day
  date.setDate(date.getDate() + offset)
  date.setHours(0, 0, 0, 0)
  return date.toISOString().slice(0, 10)
}

const addDays = (isoDate: string, days: number) => {
  const date = new Date(`${isoDate}T00:00:00`)
  date.setDate(date.getDate() + days)
  return date.toISOString().slice(0, 10)
}

const defaultSchool: SchoolProfile = {
  familySchoolName: 'Northstar Homeschool',
  state: '',
  schoolYear: '2026-2027',
  philosophy:
    'College-bound, mastery-focused, project-backed learning with strong transcripts and real work samples.',
  gradingScale: 'A=90-100, B=80-89, C=70-79; mastery revisions allowed before final grade.',
  targetColleges: '',
  accountabilityNotes:
    'State compliance, transcript format, testing, and outside evaluations need to be confirmed.',
  daysPerWeek: 4,
}

const defaultStudents: Student[] = [
  {
    id: 'younger',
    name: '12-year-old son',
    age: 12,
    gradeLevel: 'Middle school bridge',
    targetGradYear: '2031 or 2032',
    learningStyle:
      'Short lessons, concrete projects, visible progress, and frequent narration before long written work.',
    strengths: 'Curiosity, pattern-finding, practical problem solving.',
    friction: 'Long open-ended writing and vague assignments need scaffolding.',
    interests: ['engineering', 'stories', 'outdoors', 'strategy games'],
    collegeDirection:
      'Explore strengths now; build math confidence, writing fluency, and durable study habits.',
    weeklyCapacity: 18,
    priorities: ['math confidence', 'writing fluency', 'executive skills'],
  },
  {
    id: 'older',
    name: '15-year-old son',
    age: 15,
    gradeLevel: 'High school planning year',
    targetGradYear: '2029 or 2030',
    learningStyle:
      'Seminar conversations, independent blocks, clear rubrics, and serious projects with outside audiences.',
    strengths: 'Abstract thinking, independence, debate, and deep dives when the topic matters.',
    friction: 'Consistency, documentation, and turning big interests into finished evidence.',
    interests: ['computer science', 'history', 'entrepreneurship', 'film'],
    collegeDirection:
      'Build a credible high school transcript plus a distinctive project portfolio.',
    weeklyCapacity: 24,
    priorities: ['transcript credibility', 'portfolio depth', 'writing maturity'],
  },
]

const defaultCourses: Course[] = [
  {
    id: 'younger-math',
    studentId: 'younger',
    title: 'Algebra Readiness Lab',
    subject: 'Math',
    creditGoal: 0,
    weeklyHours: 4,
    level: 'foundation',
    status: 'active',
    why: 'Build the pre-algebra to algebra bridge before high school credits start to matter.',
    skills: ['fractions', 'ratios', 'linear patterns', 'mathematical narration'],
    outputs: ['weekly problem notebook', 'two teach-back videos', 'mastery checks'],
    resources: ['Khan Academy or chosen spine', 'parent-created challenge sets'],
  },
  {
    id: 'younger-humanities',
    studentId: 'younger',
    title: 'Stories, History, and Composition',
    subject: 'English',
    creditGoal: 0,
    weeklyHours: 5,
    level: 'foundation',
    status: 'active',
    why: 'Use high-interest reading and history to build discussion, vocabulary, and paragraph craft.',
    skills: ['close reading', 'summary', 'paragraph structure', 'timeline thinking'],
    outputs: ['commonplace book', 'monthly essay', 'oral narration log'],
    resources: ['family book list', 'primary-source excerpts', 'writing rubric'],
  },
  {
    id: 'younger-science',
    studentId: 'younger',
    title: 'Field Science and Lab Notebook',
    subject: 'Science',
    creditGoal: 0,
    weeklyHours: 3,
    level: 'foundation',
    status: 'planned',
    why: 'Prepare lab habits early: observation, measurement, hypothesis, and evidence.',
    skills: ['observation', 'measurement', 'data tables', 'scientific explanation'],
    outputs: ['field notebook', 'photo evidence', 'mini lab reports'],
    resources: ['local field sites', 'basic lab kit', 'library science spine'],
  },
  {
    id: 'younger-design',
    studentId: 'younger',
    title: 'Logic, Coding, and Design Studio',
    subject: 'Computer Science',
    creditGoal: 0,
    weeklyHours: 3,
    level: 'foundation',
    status: 'active',
    why: 'Turn curiosity into completed artifacts while practicing planning and debugging.',
    skills: ['logic', 'sequencing', 'debugging', 'presentation'],
    outputs: ['small game prototype', 'design journal', 'demo day'],
    resources: ['Scratch or MakeCode', 'graph paper', 'screen recordings'],
  },
  {
    id: 'older-english',
    studentId: 'older',
    title: 'Literature and Composition I',
    subject: 'English',
    creditGoal: 1,
    weeklyHours: 5,
    level: 'honors',
    status: 'active',
    why: 'Anchor transcript-grade reading and writing with a defensible paper trail.',
    skills: ['literary analysis', 'argument', 'revision', 'research citation'],
    outputs: ['reading list', 'four polished essays', 'seminar notes', 'final portfolio reflection'],
    resources: ['novel list', 'MLA guide', 'essay rubric'],
  },
  {
    id: 'older-math',
    studentId: 'older',
    title: 'Math Pathway: Placement to Algebra II',
    subject: 'Math',
    creditGoal: 1,
    weeklyHours: 5,
    level: 'standard',
    status: 'active',
    why: 'Keep the college-prep math sequence honest while placement is clarified.',
    skills: ['algebra fluency', 'geometry review', 'functions', 'test corrections'],
    outputs: ['unit mastery checks', 'correction journal', 'placement decision memo'],
    resources: ['chosen math spine', 'diagnostic tests', 'graphing tool'],
  },
  {
    id: 'older-biology',
    studentId: 'older',
    title: 'Biology With Lab',
    subject: 'Science',
    creditGoal: 1,
    weeklyHours: 5,
    level: 'honors',
    status: 'planned',
    why: 'Create a lab-science credit with enough documentation to satisfy transcript review.',
    skills: ['experimental design', 'cell biology', 'genetics', 'lab reporting'],
    outputs: ['lab notebook', 'eight formal labs', 'unit exams', 'research explainer'],
    resources: ['biology text', 'lab kit', 'microscope access'],
  },
  {
    id: 'older-history',
    studentId: 'older',
    title: 'United States History and Civics Seminar',
    subject: 'Social Studies',
    creditGoal: 1,
    weeklyHours: 4,
    level: 'honors',
    status: 'active',
    why: 'Pair narrative history with primary-source analysis and civic argument.',
    skills: ['primary-source analysis', 'timeline synthesis', 'argument', 'civic literacy'],
    outputs: ['source annotations', 'debate briefs', 'document-based essay'],
    resources: ['primary-source reader', 'maps', 'documentary list'],
  },
  {
    id: 'older-language',
    studentId: 'older',
    title: 'World Language I',
    subject: 'World Language',
    creditGoal: 1,
    weeklyHours: 4,
    level: 'standard',
    status: 'planned',
    why: 'Most college-bound plans need a sustained language sequence, not a last-minute patch.',
    skills: ['listening', 'speaking', 'reading', 'writing'],
    outputs: ['practice log', 'oral recordings', 'unit assessments'],
    resources: ['chosen language platform', 'conversation partner or tutor'],
  },
  {
    id: 'older-project',
    studentId: 'older',
    title: 'Computer Science and Personal Project Lab',
    subject: 'Elective',
    creditGoal: 0.5,
    weeklyHours: 4,
    level: 'honors',
    status: 'active',
    why: 'Give admissions context a real artifact: built, documented, revised, and presented.',
    skills: ['programming', 'product thinking', 'documentation', 'presentation'],
    outputs: ['public demo or portfolio page', 'technical writeup', 'reflection essay'],
    resources: ['GitHub', 'project rubric', 'mentor feedback'],
  },
]

const defaultModules: LearningModule[] = [
  {
    id: 'm-younger-math-1',
    courseId: 'younger-math',
    title: 'Ratios, Rates, and Real-World Scaling',
    weeks: 3,
    drivingQuestion: 'How do ratios let us compare unlike things without guessing?',
    deliverable: 'A solved design challenge with written math narration.',
    status: 'active',
  },
  {
    id: 'm-younger-humanities-1',
    courseId: 'younger-humanities',
    title: 'Hero Stories and Paragraph Craft',
    weeks: 4,
    drivingQuestion: 'What makes a person worth remembering in a story or history?',
    deliverable: 'One polished character analysis paragraph and oral narration.',
    status: 'active',
  },
  {
    id: 'm-younger-design-1',
    courseId: 'younger-design',
    title: 'Build a Tiny Strategy Game',
    weeks: 4,
    drivingQuestion: 'How do rules create interesting choices?',
    deliverable: 'Playable prototype plus design journal.',
    status: 'planned',
  },
  {
    id: 'm-older-english-1',
    courseId: 'older-english',
    title: 'Argument From Literature',
    weeks: 4,
    drivingQuestion: 'How does a writer make a moral choice feel inevitable?',
    deliverable: 'A revised 900-1200 word literary argument.',
    status: 'active',
  },
  {
    id: 'm-older-math-1',
    courseId: 'older-math',
    title: 'Functions and Placement Evidence',
    weeks: 3,
    drivingQuestion: 'What math level is actually right, based on evidence?',
    deliverable: 'Correction journal and placement memo.',
    status: 'active',
  },
  {
    id: 'm-older-history-1',
    courseId: 'older-history',
    title: 'Founding Arguments',
    weeks: 4,
    drivingQuestion: 'Which founding arguments are still unresolved?',
    deliverable: 'Document-based essay and seminar notes.',
    status: 'active',
  },
  {
    id: 'm-older-project-1',
    courseId: 'older-project',
    title: 'Portfolio Project Definition',
    weeks: 2,
    drivingQuestion: 'What can I build that proves skill and taste?',
    deliverable: 'Project brief, milestones, and first prototype.',
    status: 'active',
  },
]

const defaultInterview: InterviewAnswer[] = [
  {
    id: 'state',
    category: 'legal',
    prompt: 'What state are you homeschooling in, and are you under an umbrella, district notice, charter, or fully independent model?',
    answer: '',
  },
  {
    id: 'names',
    category: 'profile',
    prompt: 'What names or initials should the app use for your sons?',
    answer: '',
  },
  {
    id: 'math-placement',
    category: 'profile',
    prompt: 'For each son, what is the honest current math level, not the aspirational one?',
    answer: '',
  },
  {
    id: 'learning-style',
    category: 'pedagogy',
    prompt: 'Should the program lean secular, faith-integrated, classical, Charlotte Mason, project-based, unschool-ish, or mixed?',
    answer: '',
  },
  {
    id: 'college-targets',
    category: 'college',
    prompt: 'What does college-bound mean here: selective admissions, solid state university, community-college transfer path, STEM, liberal arts, athletics, or unknown?',
    answer: '',
  },
  {
    id: 'outside-validation',
    category: 'college',
    prompt: 'Which outside signals are acceptable: dual enrollment, AP exams, CLEP, online accredited courses, tutors, competitions, internships, or none?',
    answer: '',
  },
  {
    id: 'weekly-rhythm',
    category: 'operations',
    prompt: 'What weekly rhythm is realistic for your household: days per week, morning/afternoon energy, work blocks, and review day?',
    answer: '',
  },
]

const makeSession = (
  id: string,
  courseId: string,
  date: string,
  task: string,
  durationMinutes = 60,
): LearningSession => ({
  id,
  courseId,
  date,
  durationMinutes,
  task,
  status: 'todo',
  evidence: '',
  notes: '',
})

export const createInitialState = (): AppState => {
  const weekOf = mondayOfCurrentWeek()

  return {
    school: defaultSchool,
    students: defaultStudents,
    courses: defaultCourses,
    modules: defaultModules,
    interview: defaultInterview,
    weeklyPlans: [
      {
        id: 'week-younger',
        weekOf,
        studentId: 'younger',
        focus: 'Build confidence through short lessons and visible output.',
        sessions: [
          makeSession(
            's-younger-1',
            'younger-math',
            addDays(weekOf, 0),
            'Ratio warmup, correction journal, and one teach-back problem.',
            50,
          ),
          makeSession(
            's-younger-2',
            'younger-humanities',
            addDays(weekOf, 1),
            'Read, narrate, choose one strong sentence, and draft a paragraph.',
            60,
          ),
          makeSession(
            's-younger-3',
            'younger-design',
            addDays(weekOf, 2),
            'Sketch three game rules and test one with a paper prototype.',
            60,
          ),
        ],
      },
      {
        id: 'week-older',
        weekOf,
        studentId: 'older',
        focus: 'Create transcript-grade evidence, not just completed lessons.',
        sessions: [
          makeSession(
            's-older-1',
            'older-english',
            addDays(weekOf, 0),
            'Annotate reading and write a claim with two pieces of evidence.',
            75,
          ),
          makeSession(
            's-older-2',
            'older-math',
            addDays(weekOf, 1),
            'Complete function diagnostic and log corrected misses.',
            75,
          ),
          makeSession(
            's-older-3',
            'older-history',
            addDays(weekOf, 2),
            'Read two founding documents and prepare a debate brief.',
            70,
          ),
          makeSession(
            's-older-4',
            'older-project',
            addDays(weekOf, 3),
            'Write project brief and define a first prototype milestone.',
            90,
          ),
        ],
      },
    ],
  }
}

export const formatSubject = (subject: SubjectArea) => subject

export const toCsvText = (items: string[]) => items.join(', ')

export const parseCsvText = (value: string) =>
  value
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
