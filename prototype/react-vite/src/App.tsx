import {
  AlertTriangle,
  BookOpen,
  CalendarDays,
  CheckCircle2,
  ClipboardList,
  Download,
  FileText,
  GraduationCap,
  LayoutDashboard,
  ListChecks,
  Plus,
  Printer,
  RefreshCcw,
  Sparkles,
  Trash2,
  Upload,
  UserRound,
} from 'lucide-react'
import type { ChangeEvent, FormEvent } from 'react'
import { useEffect, useMemo, useRef, useState } from 'react'
import './App.css'
import {
  createInitialState,
  parseCsvText,
  planningSources,
  statusLabels,
  STORAGE_KEY,
  subjectAreas,
  subjectTargets,
  toCsvText,
} from './data'
import type {
  AppState,
  Course,
  CourseStatus,
  LearningModule,
  LearningSession,
  ModuleStatus,
  SchoolProfile,
  SessionStatus,
  Student,
  SubjectArea,
} from './types'
import type { LucideIcon } from 'lucide-react'

type TabId = 'dashboard' | 'profiles' | 'curriculum' | 'week' | 'track'

type CourseDraft = {
  title: string
  subject: SubjectArea
  creditGoal: number
  weeklyHours: number
  level: Course['level']
  status: CourseStatus
  why: string
  skillsText: string
  outputsText: string
  resourcesText: string
}

type ModuleDraft = {
  courseId: string
  title: string
  weeks: number
  drivingQuestion: string
  deliverable: string
}

type SessionDraft = {
  courseId: string
  date: string
  durationMinutes: number
  task: string
}

type ReadinessFlag = {
  title: string
  detail: string
  tone: 'warning' | 'notice' | 'good'
}

const tabs: Array<{ id: TabId; label: string; icon: LucideIcon }> = [
  { id: 'dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { id: 'profiles', label: 'Profiles', icon: UserRound },
  { id: 'curriculum', label: 'Curriculum', icon: BookOpen },
  { id: 'week', label: 'Execute', icon: CalendarDays },
  { id: 'track', label: 'Track', icon: ListChecks },
]

const courseLevelLabels: Record<Course['level'], string> = {
  foundation: 'Foundation',
  standard: 'Standard',
  honors: 'Honors',
  'ap-de-ready': 'AP / DE ready',
}

const sessionStatusLabels: Record<SessionStatus, string> = {
  todo: 'To do',
  done: 'Done',
  skipped: 'Skipped',
}

const moduleStatusLabels: Record<ModuleStatus, string> = {
  planned: 'Planned',
  active: 'Active',
  complete: 'Complete',
}

const makeId = (prefix: string) => {
  const randomPart =
    typeof crypto !== 'undefined' && 'randomUUID' in crypto
      ? crypto.randomUUID()
      : Math.random().toString(36).slice(2)
  return `${prefix}-${randomPart}`
}

const currentMonday = () => {
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

const loadState = (): AppState => {
  if (typeof localStorage === 'undefined') {
    return createInitialState()
  }

  const saved = localStorage.getItem(STORAGE_KEY)
  if (!saved) {
    return createInitialState()
  }

  try {
    const parsed = JSON.parse(saved) as AppState
    if (!parsed.school || !Array.isArray(parsed.students) || !Array.isArray(parsed.courses)) {
      return createInitialState()
    }
    return parsed
  } catch {
    return createInitialState()
  }
}

const emptyCourseDraft = (): CourseDraft => ({
  title: '',
  subject: 'English',
  creditGoal: 1,
  weeklyHours: 4,
  level: 'standard',
  status: 'planned',
  why: '',
  skillsText: '',
  outputsText: '',
  resourcesText: '',
})

const emptyModuleDraft = (courseId = ''): ModuleDraft => ({
  courseId,
  title: '',
  weeks: 4,
  drivingQuestion: '',
  deliverable: '',
})

const emptySessionDraft = (courseId = '', date = currentMonday()): SessionDraft => ({
  courseId,
  date,
  durationMinutes: 60,
  task: '',
})

const getCourseProgress = (
  course: Course,
  modules: LearningModule[],
  weeklyPlans: AppState['weeklyPlans'],
) => {
  if (course.status === 'complete') {
    return 100
  }

  const courseModules = modules.filter((module) => module.courseId === course.id)
  const completedModules = courseModules.filter((module) => module.status === 'complete').length
  const sessions = weeklyPlans
    .flatMap((plan) => plan.sessions)
    .filter((session) => session.courseId === course.id)
  const doneSessions = sessions.filter((session) => session.status === 'done').length

  const moduleScore = courseModules.length ? completedModules / courseModules.length : 0
  const sessionScore = sessions.length ? doneSessions / sessions.length : 0
  const statusFloor = course.status === 'active' ? 15 : 0
  return Math.min(99, Math.round(Math.max(statusFloor, (moduleScore * 0.65 + sessionScore * 0.35) * 100)))
}

const sumCredits = (courses: Course[]) =>
  courses.reduce((total, course) => total + Number(course.creditGoal || 0), 0)

const createReadinessFlags = (state: AppState, student: Student): ReadinessFlag[] => {
  const courses = state.courses.filter((course) => course.studentId === student.id)
  const highSchoolMode = student.age >= 14
  const evidenceCount = state.weeklyPlans
    .filter((plan) => plan.studentId === student.id)
    .flatMap((plan) => plan.sessions)
    .filter((session) => session.evidence.trim()).length
  const unanswered = state.interview.filter((item) => !item.answer.trim()).length
  const plannedCredits = sumCredits(courses)
  const worldLanguageCredits = sumCredits(courses.filter((course) => course.subject === 'World Language'))
  const labScience = courses.some(
    (course) =>
      course.subject === 'Science' &&
      [...course.outputs, ...course.resources, course.title].some((value) =>
        value.toLowerCase().includes('lab'),
      ),
  )

  const flags: ReadinessFlag[] = []

  if (!state.school.state.trim()) {
    flags.push({
      tone: 'warning',
      title: 'State compliance is unknown',
      detail: 'No, we cannot design a finished homeschool plan responsibly without the state rules pinned down.',
    })
  }

  if (!state.school.targetColleges.trim()) {
    flags.push({
      tone: 'notice',
      title: 'Target college pattern is blank',
      detail: 'Add likely colleges or a path type so credits, rigor, testing, and outside validation match reality.',
    })
  }

  if (highSchoolMode && worldLanguageCredits < 2) {
    flags.push({
      tone: 'warning',
      title: 'World language sequence is thin',
      detail: 'For a college-bound high school plan, one isolated language credit is usually not enough.',
    })
  }

  if (highSchoolMode && !labScience) {
    flags.push({
      tone: 'warning',
      title: 'Lab science evidence needs a spine',
      detail: 'High school science should produce lab records, not just reading notes.',
    })
  }

  if (highSchoolMode && plannedCredits < 6) {
    flags.push({
      tone: 'notice',
      title: 'This is a starter transcript map',
      detail: 'The app has a first-year draft; the full four-year credit arc still needs to be filled out.',
    })
  }

  if (evidenceCount < 3) {
    flags.push({
      tone: 'notice',
      title: 'Portfolio evidence is still light',
      detail: 'Mark finished sessions with links, filenames, scores, photos, or short evidence notes.',
    })
  }

  if (unanswered > 0) {
    flags.push({
      tone: 'notice',
      title: `${unanswered} personalization questions open`,
      detail: 'Answering these will make the plan less generic and easier to defend later.',
    })
  }

  if (!flags.length) {
    flags.push({
      tone: 'good',
      title: 'No urgent planning gaps showing',
      detail: 'Keep executing, logging evidence, and revisiting the transcript map each month.',
    })
  }

  return flags
}

function App() {
  const [state, setState] = useState<AppState>(() => loadState())
  const [activeTab, setActiveTab] = useState<TabId>('dashboard')
  const [selectedStudentId, setSelectedStudentId] = useState(() => state.students[0]?.id ?? '')
  const [selectedWeek, setSelectedWeek] = useState(() => currentMonday())
  const [courseDraft, setCourseDraft] = useState<CourseDraft>(() => emptyCourseDraft())
  const [moduleDraft, setModuleDraft] = useState<ModuleDraft>(() =>
    emptyModuleDraft(state.courses[0]?.id ?? ''),
  )
  const [sessionDraft, setSessionDraft] = useState<SessionDraft>(() =>
    emptySessionDraft(state.courses[0]?.id ?? '', currentMonday()),
  )
  const fileInputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
  }, [state])

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: 'auto' })
  }, [activeTab, selectedStudentId])

  const selectedStudent = useMemo(
    () => state.students.find((student) => student.id === selectedStudentId) ?? state.students[0],
    [selectedStudentId, state.students],
  )

  const selectedCourses = useMemo(
    () => state.courses.filter((course) => course.studentId === selectedStudent?.id),
    [selectedStudent?.id, state.courses],
  )

  const courseById = useMemo(() => new Map(state.courses.map((course) => [course.id, course])), [state.courses])

  const selectedWeekPlan = useMemo(
    () =>
      state.weeklyPlans.find(
        (plan) => plan.studentId === selectedStudent?.id && plan.weekOf === selectedWeek,
      ),
    [selectedStudent?.id, selectedWeek, state.weeklyPlans],
  )

  const allSelectedSessions = useMemo(
    () => state.weeklyPlans.filter((plan) => plan.studentId === selectedStudent?.id).flatMap((plan) => plan.sessions),
    [selectedStudent?.id, state.weeklyPlans],
  )

  const readinessFlags = useMemo(
    () => (selectedStudent ? createReadinessFlags(state, selectedStudent) : []),
    [selectedStudent, state],
  )

  const openQuestions = state.interview.filter((item) => !item.answer.trim())
  const activeCourses = selectedCourses.filter((course) => course.status === 'active')
  const plannedCredits = sumCredits(selectedCourses)
  const completedSessions = allSelectedSessions.filter((session) => session.status === 'done').length

  const handleSelectStudent = (studentId: string) => {
    const firstCourse = state.courses.find((course) => course.studentId === studentId)
    setSelectedStudentId(studentId)
    setModuleDraft(emptyModuleDraft(firstCourse?.id ?? ''))
    setSessionDraft(emptySessionDraft(firstCourse?.id ?? '', selectedWeek))
  }

  const updateSchool = <K extends keyof SchoolProfile>(field: K, value: SchoolProfile[K]) => {
    setState((current) => ({
      ...current,
      school: {
        ...current.school,
        [field]: value,
      },
    }))
  }

  const updateStudent = <K extends keyof Student>(studentId: string, field: K, value: Student[K]) => {
    setState((current) => ({
      ...current,
      students: current.students.map((student) =>
        student.id === studentId
          ? {
              ...student,
              [field]: value,
            }
          : student,
      ),
    }))
  }

  const updateCourse = <K extends keyof Course>(courseId: string, field: K, value: Course[K]) => {
    setState((current) => ({
      ...current,
      courses: current.courses.map((course) =>
        course.id === courseId
          ? {
              ...course,
              [field]: value,
            }
          : course,
      ),
    }))
  }

  const updateModule = <K extends keyof LearningModule>(
    moduleId: string,
    field: K,
    value: LearningModule[K],
  ) => {
    setState((current) => ({
      ...current,
      modules: current.modules.map((module) =>
        module.id === moduleId
          ? {
              ...module,
              [field]: value,
            }
          : module,
      ),
    }))
  }

  const updateSession = (
    planId: string,
    sessionId: string,
    patch: Partial<LearningSession>,
  ) => {
    setState((current) => ({
      ...current,
      weeklyPlans: current.weeklyPlans.map((plan) =>
        plan.id === planId
          ? {
              ...plan,
              sessions: plan.sessions.map((session) =>
                session.id === sessionId ? { ...session, ...patch } : session,
              ),
            }
          : plan,
      ),
    }))
  }

  const updateInterview = (questionId: string, answer: string) => {
    setState((current) => ({
      ...current,
      interview: current.interview.map((item) =>
        item.id === questionId ? { ...item, answer } : item,
      ),
    }))
  }

  const addCourse = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!selectedStudent || !courseDraft.title.trim()) {
      return
    }

    const course: Course = {
      id: makeId('course'),
      studentId: selectedStudent.id,
      title: courseDraft.title.trim(),
      subject: courseDraft.subject,
      creditGoal: Number(courseDraft.creditGoal) || 0,
      weeklyHours: Number(courseDraft.weeklyHours) || 1,
      level: courseDraft.level,
      status: courseDraft.status,
      why: courseDraft.why.trim(),
      skills: parseCsvText(courseDraft.skillsText),
      outputs: parseCsvText(courseDraft.outputsText),
      resources: parseCsvText(courseDraft.resourcesText),
    }

    setState((current) => ({
      ...current,
      courses: [...current.courses, course],
    }))
    setModuleDraft(emptyModuleDraft(course.id))
    setSessionDraft((draft) => ({ ...draft, courseId: course.id }))
    setCourseDraft(emptyCourseDraft())
  }

  const addModule = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!moduleDraft.courseId || !moduleDraft.title.trim()) {
      return
    }

    const module: LearningModule = {
      id: makeId('module'),
      courseId: moduleDraft.courseId,
      title: moduleDraft.title.trim(),
      weeks: Number(moduleDraft.weeks) || 1,
      drivingQuestion: moduleDraft.drivingQuestion.trim(),
      deliverable: moduleDraft.deliverable.trim(),
      status: 'planned',
    }

    setState((current) => ({
      ...current,
      modules: [...current.modules, module],
    }))
    setModuleDraft(emptyModuleDraft(moduleDraft.courseId))
  }

  const addSession = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!selectedStudent || !sessionDraft.courseId || !sessionDraft.task.trim()) {
      return
    }

    const session: LearningSession = {
      id: makeId('session'),
      courseId: sessionDraft.courseId,
      date: sessionDraft.date || selectedWeek,
      durationMinutes: Number(sessionDraft.durationMinutes) || 30,
      task: sessionDraft.task.trim(),
      status: 'todo',
      evidence: '',
      notes: '',
    }

    setState((current) => {
      const existingPlan = current.weeklyPlans.find(
        (plan) => plan.studentId === selectedStudent.id && plan.weekOf === selectedWeek,
      )

      if (existingPlan) {
        return {
          ...current,
          weeklyPlans: current.weeklyPlans.map((plan) =>
            plan.id === existingPlan.id
              ? {
                  ...plan,
                  sessions: [...plan.sessions, session],
                }
              : plan,
          ),
        }
      }

      return {
        ...current,
        weeklyPlans: [
          ...current.weeklyPlans,
          {
            id: makeId('week'),
            studentId: selectedStudent.id,
            weekOf: selectedWeek,
            focus: 'Fresh execution week.',
            sessions: [session],
          },
        ],
      }
    })

    setSessionDraft((draft) => ({ ...draft, task: '' }))
  }

  const deleteCourse = (courseId: string) => {
    setState((current) => ({
      ...current,
      courses: current.courses.filter((course) => course.id !== courseId),
      modules: current.modules.filter((module) => module.courseId !== courseId),
      weeklyPlans: current.weeklyPlans.map((plan) => ({
        ...plan,
        sessions: plan.sessions.filter((session) => session.courseId !== courseId),
      })),
    }))
  }

  const deleteSession = (planId: string, sessionId: string) => {
    setState((current) => ({
      ...current,
      weeklyPlans: current.weeklyPlans.map((plan) =>
        plan.id === planId
          ? {
              ...plan,
              sessions: plan.sessions.filter((session) => session.id !== sessionId),
            }
          : plan,
      ),
    }))
  }

  const buildSprint = () => {
    if (!selectedStudent) {
      return
    }

    const sprintCourses = selectedCourses
      .filter((course) => course.status === 'active' || course.status === 'planned')
      .slice(0, 5)

    const generatedModules: LearningModule[] = sprintCourses.map((course) => ({
      id: makeId('module'),
      courseId: course.id,
      title: `Six-week ${course.subject.toLowerCase()} sprint`,
      weeks: 6,
      drivingQuestion:
        course.skills[0] !== undefined
          ? `How can ${selectedStudent.name} show real growth in ${course.skills[0]}?`
          : `What would count as meaningful progress in ${course.title}?`,
      deliverable:
        course.outputs[0] !== undefined
          ? course.outputs[0]
          : `Finished artifact or assessment for ${course.title}.`,
      status: 'planned',
    }))

    setState((current) => ({
      ...current,
      modules: [...current.modules, ...generatedModules],
    }))
  }

  const generateWeek = () => {
    if (!selectedStudent) {
      return
    }

    const daysPerWeek = Math.max(1, Math.min(5, state.school.daysPerWeek || 4))
    const coursesForWeek = selectedCourses
      .filter((course) => course.status === 'active')
      .slice(0, daysPerWeek + 1)

    if (!coursesForWeek.length) {
      return
    }

    const generatedSessions: LearningSession[] = coursesForWeek.map((course, index) => ({
      id: makeId('session'),
      courseId: course.id,
      date: addDays(selectedWeek, index % daysPerWeek),
      durationMinutes: Math.max(45, Math.round((course.weeklyHours * 60) / 2)),
      task:
        course.outputs[0] !== undefined
          ? `${course.title}: move one step toward ${course.outputs[0]}.`
          : `${course.title}: complete the next lesson and log evidence.`,
      status: 'todo',
      evidence: '',
      notes: '',
    }))

    setState((current) => {
      const existingPlan = current.weeklyPlans.find(
        (plan) => plan.studentId === selectedStudent.id && plan.weekOf === selectedWeek,
      )

      if (existingPlan) {
        return {
          ...current,
          weeklyPlans: current.weeklyPlans.map((plan) =>
            plan.id === existingPlan.id
              ? {
                  ...plan,
                  sessions: [...plan.sessions, ...generatedSessions],
                }
              : plan,
          ),
        }
      }

      return {
        ...current,
        weeklyPlans: [
          ...current.weeklyPlans,
          {
            id: makeId('week'),
            studentId: selectedStudent.id,
            weekOf: selectedWeek,
            focus: `Convert ${selectedStudent.name}'s active courses into visible evidence.`,
            sessions: generatedSessions,
          },
        ],
      }
    })
  }

  const exportPlan = () => {
    const file = new Blob([JSON.stringify(state, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(file)
    const link = document.createElement('a')
    link.href = url
    link.download = `${state.school.familySchoolName || 'homeschool'}-plan.json`
    link.click()
    URL.revokeObjectURL(url)
  }

  const importPlan = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (!file) {
      return
    }

    const reader = new FileReader()
    reader.onload = () => {
      try {
        const parsed = JSON.parse(String(reader.result)) as AppState
        if (!parsed.school || !Array.isArray(parsed.students) || !Array.isArray(parsed.courses)) {
          window.alert('That JSON file does not look like a homeschool plan export.')
          return
        }
        setState(parsed)
        setSelectedStudentId(parsed.students[0]?.id ?? '')
        setModuleDraft(emptyModuleDraft(parsed.courses[0]?.id ?? ''))
        setSessionDraft(emptySessionDraft(parsed.courses[0]?.id ?? '', selectedWeek))
      } catch {
        window.alert('Could not read that JSON file.')
      } finally {
        if (fileInputRef.current) {
          fileInputRef.current.value = ''
        }
      }
    }
    reader.readAsText(file)
  }

  const resetToStarter = () => {
    const nextState = createInitialState()
    const monday = currentMonday()
    setState(nextState)
    setSelectedStudentId(nextState.students[0]?.id ?? '')
    setModuleDraft(emptyModuleDraft(nextState.courses[0]?.id ?? ''))
    setSessionDraft(emptySessionDraft(nextState.courses[0]?.id ?? '', monday))
    setSelectedWeek(monday)
  }

  if (!selectedStudent) {
    return <main className="empty-state">No learner profile found.</main>
  }

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="brand-block">
          <div className="brand-mark" aria-hidden="true">
            <GraduationCap size={24} />
          </div>
          <div>
            <p className="eyebrow">Private local planner</p>
            <h1>Northstar</h1>
          </div>
        </div>

        <nav className="nav-list" aria-label="Primary navigation">
          {tabs.map((tab) => {
            const Icon = tab.icon
            return (
              <button
                key={tab.id}
                type="button"
                className={activeTab === tab.id ? 'nav-item active' : 'nav-item'}
                onClick={() => setActiveTab(tab.id)}
              >
                <Icon size={18} />
                <span>{tab.label}</span>
              </button>
            )
          })}
        </nav>

        <label className="sidebar-label" htmlFor="student-select">
          Learner
        </label>
        <select
          id="student-select"
          value={selectedStudent.id}
          onChange={(event) => handleSelectStudent(event.target.value)}
        >
          {state.students.map((student) => (
            <option key={student.id} value={student.id}>
              {student.name}
            </option>
          ))}
        </select>

        <div className="sidebar-actions">
          <button type="button" className="ghost-button" onClick={exportPlan}>
            <Download size={16} />
            Export
          </button>
          <button type="button" className="ghost-button" onClick={() => fileInputRef.current?.click()}>
            <Upload size={16} />
            Import
          </button>
          <button type="button" className="ghost-button" onClick={() => window.print()}>
            <Printer size={16} />
            Print
          </button>
          <button type="button" className="ghost-button danger" onClick={resetToStarter}>
            <RefreshCcw size={16} />
            Reset
          </button>
        </div>
        <input
          ref={fileInputRef}
          hidden
          type="file"
          accept="application/json"
          onChange={importPlan}
        />
      </aside>

      <main className="workspace">
        <header className="topbar">
          <div>
            <p className="eyebrow">{state.school.schoolYear}</p>
            <h2>{state.school.familySchoolName}</h2>
          </div>
          <div className="topbar-summary">
            <span>{selectedStudent.gradeLevel}</span>
            <span>{plannedCredits.toFixed(1)} planned credits</span>
            <span>{completedSessions} completed sessions</span>
          </div>
        </header>

        {activeTab === 'dashboard' && (
          <DashboardView
            activeCourses={activeCourses}
            allSelectedSessions={allSelectedSessions}
            completedSessions={completedSessions}
            courseById={courseById}
            openQuestions={openQuestions.length}
            plannedCredits={plannedCredits}
            readinessFlags={readinessFlags}
            selectedCourses={selectedCourses}
            selectedStudent={selectedStudent}
            setActiveTab={setActiveTab}
            state={state}
          />
        )}

        {activeTab === 'profiles' && (
          <ProfilesView
            state={state}
            updateInterview={updateInterview}
            updateSchool={updateSchool}
            updateStudent={updateStudent}
          />
        )}

        {activeTab === 'curriculum' && (
          <CurriculumView
            addCourse={addCourse}
            addModule={addModule}
            buildSprint={buildSprint}
            courseDraft={courseDraft}
            deleteCourse={deleteCourse}
            moduleDraft={moduleDraft}
            selectedCourses={selectedCourses}
            selectedStudent={selectedStudent}
            setCourseDraft={setCourseDraft}
            setModuleDraft={setModuleDraft}
            state={state}
            updateCourse={updateCourse}
            updateModule={updateModule}
          />
        )}

        {activeTab === 'week' && (
          <ExecuteView
            addSession={addSession}
            courseById={courseById}
            deleteSession={deleteSession}
            generateWeek={generateWeek}
            selectedCourses={selectedCourses}
            selectedStudent={selectedStudent}
            selectedWeek={selectedWeek}
            selectedWeekPlan={selectedWeekPlan}
            sessionDraft={sessionDraft}
            setSelectedWeek={setSelectedWeek}
            setSessionDraft={setSessionDraft}
            updateSession={updateSession}
          />
        )}

        {activeTab === 'track' && (
          <TrackView
            allSelectedSessions={allSelectedSessions}
            courseById={courseById}
            selectedCourses={selectedCourses}
            selectedStudent={selectedStudent}
            state={state}
          />
        )}
      </main>
    </div>
  )
}

function DashboardView({
  activeCourses,
  allSelectedSessions,
  completedSessions,
  courseById,
  openQuestions,
  plannedCredits,
  readinessFlags,
  selectedCourses,
  selectedStudent,
  setActiveTab,
  state,
}: {
  activeCourses: Course[]
  allSelectedSessions: LearningSession[]
  completedSessions: number
  courseById: Map<string, Course>
  openQuestions: number
  plannedCredits: number
  readinessFlags: ReadinessFlag[]
  selectedCourses: Course[]
  selectedStudent: Student
  setActiveTab: (tab: TabId) => void
  state: AppState
}) {
  const upcomingSessions = allSelectedSessions
    .filter((session) => session.status !== 'done')
    .slice(0, 5)

  return (
    <div className="view-stack">
      <section className="intro-band">
        <div>
          <p className="eyebrow">College-bound homeschool assistant</p>
          <h2>{selectedStudent.name}</h2>
          <p className="lede">{selectedStudent.collegeDirection}</p>
        </div>
        <div className="quick-actions">
          <button type="button" className="primary-button" onClick={() => setActiveTab('week')}>
            <CalendarDays size={17} />
            Execute week
          </button>
          <button type="button" className="secondary-button" onClick={() => setActiveTab('curriculum')}>
            <Sparkles size={17} />
            Build plan
          </button>
        </div>
      </section>

      <section className="metric-grid" aria-label="Plan metrics">
        <Metric label="Active courses" value={String(activeCourses.length)} detail="Courses currently in motion" />
        <Metric label="Planned credits" value={plannedCredits.toFixed(1)} detail="Editable transcript intent" />
        <Metric label="Done sessions" value={String(completedSessions)} detail="Execution records logged" />
        <Metric label="Open questions" value={String(openQuestions)} detail="Personalization still missing" />
      </section>

      <div className="two-column">
        <section className="tool-panel">
          <PanelHeading icon={CalendarDays} title="Next Work" />
          {upcomingSessions.length ? (
            <div className="session-list compact">
              {upcomingSessions.map((session) => {
                const course = courseById.get(session.courseId)
                return (
                  <article key={session.id} className="session-row">
                    <div>
                      <p className="item-title">{course?.title ?? 'Unknown course'}</p>
                      <p className="muted">{session.task}</p>
                    </div>
                    <time>{session.date}</time>
                  </article>
                )
              })}
            </div>
          ) : (
            <p className="muted">No open sessions. Generate the next week or add work manually.</p>
          )}
        </section>

        <section className="tool-panel">
          <PanelHeading icon={AlertTriangle} title="Planning Risks" />
          <div className="flag-list">
            {readinessFlags.map((flag) => (
              <article key={flag.title} className={`flag-row ${flag.tone}`}>
                <strong>{flag.title}</strong>
                <p>{flag.detail}</p>
              </article>
            ))}
          </div>
        </section>
      </div>

      <section className="tool-panel">
        <PanelHeading icon={GraduationCap} title="Credit Map" />
        <CreditMap courses={selectedCourses} />
      </section>

      <section className="tool-panel">
        <PanelHeading icon={FileText} title="Planning References" />
        <div className="source-grid">
          {planningSources.map((source) => (
            <a key={source.url} className="source-link" href={source.url} target="_blank" rel="noreferrer">
              <strong>{source.title}</strong>
              <span>{source.note}</span>
            </a>
          ))}
        </div>
        <p className="footnote">
          These are planning anchors, not legal advice. Your state rules and target colleges win when they disagree.
        </p>
      </section>

      <section className="tool-panel print-only">
        <PanelHeading icon={ClipboardList} title="Profile Snapshot" />
        <p>{state.school.philosophy}</p>
      </section>
    </div>
  )
}

function ProfilesView({
  state,
  updateInterview,
  updateSchool,
  updateStudent,
}: {
  state: AppState
  updateInterview: (questionId: string, answer: string) => void
  updateSchool: <K extends keyof SchoolProfile>(field: K, value: SchoolProfile[K]) => void
  updateStudent: <K extends keyof Student>(studentId: string, field: K, value: Student[K]) => void
}) {
  return (
    <div className="view-stack">
      <section className="tool-panel">
        <PanelHeading icon={GraduationCap} title="Family School Profile" />
        <div className="form-grid">
          <label>
            School name
            <input
              value={state.school.familySchoolName}
              onChange={(event) => updateSchool('familySchoolName', event.target.value)}
            />
          </label>
          <label>
            State
            <input
              value={state.school.state}
              placeholder="Required for compliance planning"
              onChange={(event) => updateSchool('state', event.target.value)}
            />
          </label>
          <label>
            School year
            <input
              value={state.school.schoolYear}
              onChange={(event) => updateSchool('schoolYear', event.target.value)}
            />
          </label>
          <label>
            Days per week
            <input
              type="number"
              min="1"
              max="6"
              value={state.school.daysPerWeek}
              onChange={(event) => updateSchool('daysPerWeek', Number(event.target.value))}
            />
          </label>
        </div>
        <label>
          Philosophy
          <textarea
            value={state.school.philosophy}
            onChange={(event) => updateSchool('philosophy', event.target.value)}
          />
        </label>
        <div className="form-grid">
          <label>
            Grading scale
            <textarea
              value={state.school.gradingScale}
              onChange={(event) => updateSchool('gradingScale', event.target.value)}
            />
          </label>
          <label>
            Target colleges or path
            <textarea
              value={state.school.targetColleges}
              placeholder="State flagship, selective STEM, liberal arts, community-college transfer..."
              onChange={(event) => updateSchool('targetColleges', event.target.value)}
            />
          </label>
        </div>
        <label>
          Compliance and accountability notes
          <textarea
            value={state.school.accountabilityNotes}
            onChange={(event) => updateSchool('accountabilityNotes', event.target.value)}
          />
        </label>
      </section>

      <section className="learner-grid" aria-label="Learner profiles">
        {state.students.map((student) => (
          <article key={student.id} className="tool-panel">
            <PanelHeading icon={UserRound} title={student.name} />
            <div className="form-grid">
              <label>
                Name
                <input
                  value={student.name}
                  onChange={(event) => updateStudent(student.id, 'name', event.target.value)}
                />
              </label>
              <label>
                Age
                <input
                  type="number"
                  min="1"
                  value={student.age}
                  onChange={(event) => updateStudent(student.id, 'age', Number(event.target.value))}
                />
              </label>
              <label>
                Grade or level
                <input
                  value={student.gradeLevel}
                  onChange={(event) => updateStudent(student.id, 'gradeLevel', event.target.value)}
                />
              </label>
              <label>
                Target graduation year
                <input
                  value={student.targetGradYear}
                  onChange={(event) => updateStudent(student.id, 'targetGradYear', event.target.value)}
                />
              </label>
            </div>
            <label>
              Learning style
              <textarea
                value={student.learningStyle}
                onChange={(event) => updateStudent(student.id, 'learningStyle', event.target.value)}
              />
            </label>
            <div className="form-grid">
              <label>
                Strengths
                <textarea
                  value={student.strengths}
                  onChange={(event) => updateStudent(student.id, 'strengths', event.target.value)}
                />
              </label>
              <label>
                Friction
                <textarea
                  value={student.friction}
                  onChange={(event) => updateStudent(student.id, 'friction', event.target.value)}
                />
              </label>
            </div>
            <label>
              Interests
              <input
                value={toCsvText(student.interests)}
                onChange={(event) =>
                  updateStudent(student.id, 'interests', parseCsvText(event.target.value))
                }
              />
            </label>
            <label>
              College direction
              <textarea
                value={student.collegeDirection}
                onChange={(event) => updateStudent(student.id, 'collegeDirection', event.target.value)}
              />
            </label>
            <div className="form-grid">
              <label>
                Weekly capacity hours
                <input
                  type="number"
                  min="1"
                  value={student.weeklyCapacity}
                  onChange={(event) => updateStudent(student.id, 'weeklyCapacity', Number(event.target.value))}
                />
              </label>
              <label>
                Priorities
                <input
                  value={toCsvText(student.priorities)}
                  onChange={(event) =>
                    updateStudent(student.id, 'priorities', parseCsvText(event.target.value))
                  }
                />
              </label>
            </div>
          </article>
        ))}
      </section>

      <section className="tool-panel">
        <PanelHeading icon={ClipboardList} title="Guided Interview" />
        <div className="question-list">
          {state.interview.map((item) => (
            <label key={item.id} className="question-item">
              <span>
                <strong>{item.prompt}</strong>
                <em>{item.category}</em>
              </span>
              <textarea value={item.answer} onChange={(event) => updateInterview(item.id, event.target.value)} />
            </label>
          ))}
        </div>
      </section>
    </div>
  )
}

function CurriculumView({
  addCourse,
  addModule,
  buildSprint,
  courseDraft,
  deleteCourse,
  moduleDraft,
  selectedCourses,
  selectedStudent,
  setCourseDraft,
  setModuleDraft,
  state,
  updateCourse,
  updateModule,
}: {
  addCourse: (event: FormEvent<HTMLFormElement>) => void
  addModule: (event: FormEvent<HTMLFormElement>) => void
  buildSprint: () => void
  courseDraft: CourseDraft
  deleteCourse: (courseId: string) => void
  moduleDraft: ModuleDraft
  selectedCourses: Course[]
  selectedStudent: Student
  setCourseDraft: (draft: CourseDraft | ((draft: CourseDraft) => CourseDraft)) => void
  setModuleDraft: (draft: ModuleDraft | ((draft: ModuleDraft) => ModuleDraft)) => void
  state: AppState
  updateCourse: <K extends keyof Course>(courseId: string, field: K, value: Course[K]) => void
  updateModule: <K extends keyof LearningModule>(
    moduleId: string,
    field: K,
    value: LearningModule[K],
  ) => void
}) {
  return (
    <div className="view-stack">
      <section className="intro-band compact-band">
        <div>
          <p className="eyebrow">Curriculum design studio</p>
          <h2>{selectedStudent.name}</h2>
          <p className="lede">
            Build courses from outcomes backward, then attach modules and evidence. A course without outputs is
            just a wish.
          </p>
        </div>
        <button type="button" className="primary-button" onClick={buildSprint}>
          <Sparkles size={17} />
          Build 6-week sprint
        </button>
      </section>

      <section className="tool-panel">
        <PanelHeading icon={Plus} title="Add Course" />
        <form className="course-form" onSubmit={addCourse}>
          <div className="form-grid four">
            <label>
              Title
              <input
                value={courseDraft.title}
                placeholder="Course title"
                onChange={(event) =>
                  setCourseDraft((draft) => ({ ...draft, title: event.target.value }))
                }
              />
            </label>
            <label>
              Subject
              <select
                value={courseDraft.subject}
                onChange={(event) =>
                  setCourseDraft((draft) => ({
                    ...draft,
                    subject: event.target.value as SubjectArea,
                  }))
                }
              >
                {subjectAreas.map((subject) => (
                  <option key={subject} value={subject}>
                    {subject}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Credits
              <input
                type="number"
                min="0"
                step="0.25"
                value={courseDraft.creditGoal}
                onChange={(event) =>
                  setCourseDraft((draft) => ({
                    ...draft,
                    creditGoal: Number(event.target.value),
                  }))
                }
              />
            </label>
            <label>
              Weekly hours
              <input
                type="number"
                min="1"
                value={courseDraft.weeklyHours}
                onChange={(event) =>
                  setCourseDraft((draft) => ({
                    ...draft,
                    weeklyHours: Number(event.target.value),
                  }))
                }
              />
            </label>
          </div>
          <div className="form-grid">
            <label>
              Level
              <select
                value={courseDraft.level}
                onChange={(event) =>
                  setCourseDraft((draft) => ({
                    ...draft,
                    level: event.target.value as Course['level'],
                  }))
                }
              >
                {Object.entries(courseLevelLabels).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Status
              <select
                value={courseDraft.status}
                onChange={(event) =>
                  setCourseDraft((draft) => ({
                    ...draft,
                    status: event.target.value as CourseStatus,
                  }))
                }
              >
                {Object.entries(statusLabels).map(([value, label]) => (
                  <option key={value} value={value}>
                    {label}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <label>
            Why this course
            <textarea
              value={courseDraft.why}
              onChange={(event) => setCourseDraft((draft) => ({ ...draft, why: event.target.value }))}
            />
          </label>
          <div className="form-grid">
            <label>
              Skills
              <input
                value={courseDraft.skillsText}
                placeholder="comma-separated"
                onChange={(event) =>
                  setCourseDraft((draft) => ({ ...draft, skillsText: event.target.value }))
                }
              />
            </label>
            <label>
              Outputs
              <input
                value={courseDraft.outputsText}
                placeholder="essays, labs, demos, exams..."
                onChange={(event) =>
                  setCourseDraft((draft) => ({ ...draft, outputsText: event.target.value }))
                }
              />
            </label>
          </div>
          <label>
            Resources
            <input
              value={courseDraft.resourcesText}
              placeholder="books, course spine, mentor, tool..."
              onChange={(event) =>
                setCourseDraft((draft) => ({ ...draft, resourcesText: event.target.value }))
              }
            />
          </label>
          <button type="submit" className="primary-button">
            <Plus size={17} />
            Add course
          </button>
        </form>
      </section>

      <section className="tool-panel">
        <PanelHeading icon={BookOpen} title="Course Map" />
        <div className="course-list">
          {selectedCourses.map((course) => {
            const courseModules = state.modules.filter((module) => module.courseId === course.id)
            const progress = getCourseProgress(course, state.modules, state.weeklyPlans)
            return (
              <article key={course.id} className="course-card">
                <div className="course-card-head">
                  <div>
                    <p className="eyebrow">{course.subject}</p>
                    <input
                      className="inline-title-input"
                      value={course.title}
                      onChange={(event) => updateCourse(course.id, 'title', event.target.value)}
                    />
                  </div>
                  <button
                    type="button"
                    className="icon-button danger"
                    aria-label={`Delete ${course.title}`}
                    onClick={() => deleteCourse(course.id)}
                  >
                    <Trash2 size={17} />
                  </button>
                </div>

                <div className="progress-track" aria-label={`${progress}% complete`}>
                  <span style={{ width: `${progress}%` }} />
                </div>

                <div className="form-grid four">
                  <label>
                    Status
                    <select
                      value={course.status}
                      onChange={(event) =>
                        updateCourse(course.id, 'status', event.target.value as CourseStatus)
                      }
                    >
                      {Object.entries(statusLabels).map(([value, label]) => (
                        <option key={value} value={value}>
                          {label}
                        </option>
                      ))}
                    </select>
                  </label>
                  <label>
                    Subject
                    <select
                      value={course.subject}
                      onChange={(event) =>
                        updateCourse(course.id, 'subject', event.target.value as SubjectArea)
                      }
                    >
                      {subjectAreas.map((subject) => (
                        <option key={subject} value={subject}>
                          {subject}
                        </option>
                      ))}
                    </select>
                  </label>
                  <label>
                    Credits
                    <input
                      type="number"
                      min="0"
                      step="0.25"
                      value={course.creditGoal}
                      onChange={(event) =>
                        updateCourse(course.id, 'creditGoal', Number(event.target.value))
                      }
                    />
                  </label>
                  <label>
                    Hours
                    <input
                      type="number"
                      min="1"
                      value={course.weeklyHours}
                      onChange={(event) =>
                        updateCourse(course.id, 'weeklyHours', Number(event.target.value))
                      }
                    />
                  </label>
                </div>

                <label>
                  Why
                  <textarea
                    value={course.why}
                    onChange={(event) => updateCourse(course.id, 'why', event.target.value)}
                  />
                </label>
                <div className="form-grid">
                  <label>
                    Skills
                    <input
                      value={toCsvText(course.skills)}
                      onChange={(event) =>
                        updateCourse(course.id, 'skills', parseCsvText(event.target.value))
                      }
                    />
                  </label>
                  <label>
                    Outputs
                    <input
                      value={toCsvText(course.outputs)}
                      onChange={(event) =>
                        updateCourse(course.id, 'outputs', parseCsvText(event.target.value))
                      }
                    />
                  </label>
                </div>

                <div className="module-strip">
                  {courseModules.length ? (
                    courseModules.map((module) => (
                      <div key={module.id} className="module-row">
                        <div>
                          <strong>{module.title}</strong>
                          <p>{module.deliverable}</p>
                        </div>
                        <select
                          value={module.status}
                          onChange={(event) =>
                            updateModule(module.id, 'status', event.target.value as ModuleStatus)
                          }
                        >
                          {Object.entries(moduleStatusLabels).map(([value, label]) => (
                            <option key={value} value={value}>
                              {label}
                            </option>
                          ))}
                        </select>
                      </div>
                    ))
                  ) : (
                    <p className="muted">No modules yet.</p>
                  )}
                </div>
              </article>
            )
          })}
        </div>
      </section>

      <section className="tool-panel">
        <PanelHeading icon={Plus} title="Add Module" />
        <form className="course-form" onSubmit={addModule}>
          <div className="form-grid four">
            <label>
              Course
              <select
                value={moduleDraft.courseId}
                onChange={(event) =>
                  setModuleDraft((draft) => ({ ...draft, courseId: event.target.value }))
                }
              >
                {selectedCourses.map((course) => (
                  <option key={course.id} value={course.id}>
                    {course.title}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Title
              <input
                value={moduleDraft.title}
                onChange={(event) =>
                  setModuleDraft((draft) => ({ ...draft, title: event.target.value }))
                }
              />
            </label>
            <label>
              Weeks
              <input
                type="number"
                min="1"
                value={moduleDraft.weeks}
                onChange={(event) =>
                  setModuleDraft((draft) => ({ ...draft, weeks: Number(event.target.value) }))
                }
              />
            </label>
          </div>
          <div className="form-grid">
            <label>
              Driving question
              <textarea
                value={moduleDraft.drivingQuestion}
                onChange={(event) =>
                  setModuleDraft((draft) => ({ ...draft, drivingQuestion: event.target.value }))
                }
              />
            </label>
            <label>
              Deliverable
              <textarea
                value={moduleDraft.deliverable}
                onChange={(event) =>
                  setModuleDraft((draft) => ({ ...draft, deliverable: event.target.value }))
                }
              />
            </label>
          </div>
          <button type="submit" className="primary-button">
            <Plus size={17} />
            Add module
          </button>
        </form>
      </section>
    </div>
  )
}

function ExecuteView({
  addSession,
  courseById,
  deleteSession,
  generateWeek,
  selectedCourses,
  selectedStudent,
  selectedWeek,
  selectedWeekPlan,
  sessionDraft,
  setSelectedWeek,
  setSessionDraft,
  updateSession,
}: {
  addSession: (event: FormEvent<HTMLFormElement>) => void
  courseById: Map<string, Course>
  deleteSession: (planId: string, sessionId: string) => void
  generateWeek: () => void
  selectedCourses: Course[]
  selectedStudent: Student
  selectedWeek: string
  selectedWeekPlan: AppState['weeklyPlans'][number] | undefined
  sessionDraft: SessionDraft
  setSelectedWeek: (week: string) => void
  setSessionDraft: (draft: SessionDraft | ((draft: SessionDraft) => SessionDraft)) => void
  updateSession: (planId: string, sessionId: string, patch: Partial<LearningSession>) => void
}) {
  const planId = selectedWeekPlan?.id ?? ''

  return (
    <div className="view-stack">
      <section className="intro-band compact-band">
        <div>
          <p className="eyebrow">Execution week</p>
          <h2>{selectedStudent.name}</h2>
          <p className="lede">A beautiful plan that never becomes evidence is just stationery.</p>
        </div>
        <div className="week-controls">
          <input type="date" value={selectedWeek} onChange={(event) => setSelectedWeek(event.target.value)} />
          <button type="button" className="primary-button" onClick={generateWeek}>
            <Sparkles size={17} />
            Generate week
          </button>
        </div>
      </section>

      <section className="tool-panel">
        <PanelHeading icon={Plus} title="Add Session" />
        <form className="course-form" onSubmit={addSession}>
          <div className="form-grid four">
            <label>
              Course
              <select
                value={sessionDraft.courseId}
                onChange={(event) =>
                  setSessionDraft((draft) => ({ ...draft, courseId: event.target.value }))
                }
              >
                {selectedCourses.map((course) => (
                  <option key={course.id} value={course.id}>
                    {course.title}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Date
              <input
                type="date"
                value={sessionDraft.date}
                onChange={(event) =>
                  setSessionDraft((draft) => ({ ...draft, date: event.target.value }))
                }
              />
            </label>
            <label>
              Minutes
              <input
                type="number"
                min="10"
                value={sessionDraft.durationMinutes}
                onChange={(event) =>
                  setSessionDraft((draft) => ({
                    ...draft,
                    durationMinutes: Number(event.target.value),
                  }))
                }
              />
            </label>
          </div>
          <label>
            Task
            <input
              value={sessionDraft.task}
              onChange={(event) => setSessionDraft((draft) => ({ ...draft, task: event.target.value }))}
            />
          </label>
          <button type="submit" className="primary-button">
            <Plus size={17} />
            Add session
          </button>
        </form>
      </section>

      <section className="tool-panel">
        <PanelHeading icon={CalendarDays} title="Week Board" />
        {selectedWeekPlan?.sessions.length ? (
          <div className="session-list">
            {selectedWeekPlan.sessions.map((session) => {
              const course = courseById.get(session.courseId)
              return (
                <article key={session.id} className="session-card">
                  <div className="session-main">
                    <div>
                      <p className="eyebrow">{session.date}</p>
                      <h3>{course?.title ?? 'Unknown course'}</h3>
                    </div>
                    <button
                      type="button"
                      className="icon-button danger"
                      aria-label="Delete session"
                      onClick={() => deleteSession(planId, session.id)}
                    >
                      <Trash2 size={17} />
                    </button>
                  </div>
                  <label>
                    Task
                    <textarea
                      value={session.task}
                      onChange={(event) =>
                        updateSession(planId, session.id, { task: event.target.value })
                      }
                    />
                  </label>
                  <div className="form-grid">
                    <label>
                      Minutes
                      <input
                        type="number"
                        min="10"
                        value={session.durationMinutes}
                        onChange={(event) =>
                          updateSession(planId, session.id, {
                            durationMinutes: Number(event.target.value),
                          })
                        }
                      />
                    </label>
                    <div className="segmented" aria-label="Session status">
                      {Object.entries(sessionStatusLabels).map(([value, label]) => (
                        <button
                          key={value}
                          type="button"
                          className={session.status === value ? 'active' : ''}
                          onClick={() => updateSession(planId, session.id, { status: value as SessionStatus })}
                        >
                          {label}
                        </button>
                      ))}
                    </div>
                  </div>
                  <div className="form-grid">
                    <label>
                      Evidence
                      <textarea
                        value={session.evidence}
                        placeholder="Score, file, link, photo note, essay title, lab number..."
                        onChange={(event) =>
                          updateSession(planId, session.id, { evidence: event.target.value })
                        }
                      />
                    </label>
                    <label>
                      Notes
                      <textarea
                        value={session.notes}
                        onChange={(event) =>
                          updateSession(planId, session.id, { notes: event.target.value })
                        }
                      />
                    </label>
                  </div>
                </article>
              )
            })}
          </div>
        ) : (
          <p className="muted">No sessions for this week yet. Generate a week or add a session.</p>
        )}
      </section>
    </div>
  )
}

function TrackView({
  allSelectedSessions,
  courseById,
  selectedCourses,
  selectedStudent,
  state,
}: {
  allSelectedSessions: LearningSession[]
  courseById: Map<string, Course>
  selectedCourses: Course[]
  selectedStudent: Student
  state: AppState
}) {
  const evidenceSessions = allSelectedSessions.filter((session) => session.evidence.trim())
  const checklist = [
    ['State homeschool requirement checked', Boolean(state.school.state.trim())],
    ['Target college pattern recorded', Boolean(state.school.targetColleges.trim())],
    ['Course descriptions started', selectedCourses.some((course) => course.why.trim())],
    ['Lab science evidence planned', selectedCourses.some((course) => course.subject === 'Science')],
    ['World language sequence started', selectedCourses.some((course) => course.subject === 'World Language')],
    ['Portfolio evidence logged', evidenceSessions.length >= 3],
    ['School profile language drafted', Boolean(state.school.philosophy.trim())],
  ] as const

  return (
    <div className="view-stack">
      <section className="intro-band compact-band">
        <div>
          <p className="eyebrow">Tracking and transcript readiness</p>
          <h2>{selectedStudent.name}</h2>
          <p className="lede">
            Track credits, artifacts, and admissions-context documents while the work is still fresh.
          </p>
        </div>
      </section>

      <section className="tool-panel">
        <PanelHeading icon={GraduationCap} title="Credit Progress" />
        <CreditMap courses={selectedCourses} />
      </section>

      <section className="tool-panel">
        <PanelHeading icon={FileText} title="Transcript Snapshot" />
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Course</th>
                <th>Subject</th>
                <th>Credits</th>
                <th>Level</th>
                <th>Status</th>
                <th>Progress</th>
              </tr>
            </thead>
            <tbody>
              {selectedCourses.map((course) => (
                <tr key={course.id}>
                  <td>{course.title}</td>
                  <td>{course.subject}</td>
                  <td>{course.creditGoal.toFixed(2)}</td>
                  <td>{courseLevelLabels[course.level]}</td>
                  <td>{statusLabels[course.status]}</td>
                  <td>{getCourseProgress(course, state.modules, state.weeklyPlans)}%</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <div className="two-column">
        <section className="tool-panel">
          <PanelHeading icon={CheckCircle2} title="College File Checklist" />
          <div className="check-list">
            {checklist.map(([label, done]) => (
              <div key={label} className={done ? 'check-row done' : 'check-row'}>
                <CheckCircle2 size={17} />
                <span>{label}</span>
              </div>
            ))}
          </div>
        </section>

        <section className="tool-panel">
          <PanelHeading icon={ClipboardList} title="Evidence Log" />
          {evidenceSessions.length ? (
            <div className="evidence-list">
              {evidenceSessions.map((session) => {
                const course = courseById.get(session.courseId)
                return (
                  <article key={session.id} className="evidence-row">
                    <strong>{course?.title ?? 'Unknown course'}</strong>
                    <span>{session.date}</span>
                    <p>{session.evidence}</p>
                  </article>
                )
              })}
            </div>
          ) : (
            <p className="muted">Evidence notes will appear here after sessions are marked with artifacts.</p>
          )}
        </section>
      </div>
    </div>
  )
}

function Metric({ label, value, detail }: { label: string; value: string; detail: string }) {
  return (
    <article className="metric">
      <span>{label}</span>
      <strong>{value}</strong>
      <p>{detail}</p>
    </article>
  )
}

function PanelHeading({ icon: Icon, title }: { icon: LucideIcon; title: string }) {
  return (
    <div className="panel-heading">
      <Icon size={18} />
      <h3>{title}</h3>
    </div>
  )
}

function CreditMap({ courses }: { courses: Course[] }) {
  return (
    <div className="credit-map">
      {subjectTargets.map((target) => {
        const subjectCourses = courses.filter((course) => course.subject === target.subject)
        const planned = sumCredits(subjectCourses)
        const completed = sumCredits(subjectCourses.filter((course) => course.status === 'complete'))
        const plannedPercent = Math.min(100, (planned / target.preferred) * 100)
        const completedPercent = Math.min(100, (completed / target.preferred) * 100)

        return (
          <article key={target.subject} className="credit-row">
            <div className="credit-label">
              <strong>{target.subject}</strong>
              <span>
                {planned.toFixed(1)} / {target.preferred.toFixed(1)} credits
              </span>
            </div>
            <div className="credit-bars" aria-label={`${target.subject} credit progress`}>
              <span className="planned" style={{ width: `${plannedPercent}%` }} />
              <span className="completed" style={{ width: `${completedPercent}%` }} />
            </div>
            <p>{target.note}</p>
          </article>
        )
      })}
    </div>
  )
}

export default App
