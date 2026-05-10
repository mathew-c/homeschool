export type SubjectArea =
  | 'English'
  | 'Math'
  | 'Science'
  | 'Social Studies'
  | 'World Language'
  | 'Arts'
  | 'Computer Science'
  | 'Wellness'
  | 'Elective'

export type CourseStatus = 'planned' | 'active' | 'complete' | 'paused'
export type ModuleStatus = 'planned' | 'active' | 'complete'
export type SessionStatus = 'todo' | 'done' | 'skipped'

export type SchoolProfile = {
  familySchoolName: string
  state: string
  schoolYear: string
  philosophy: string
  gradingScale: string
  targetColleges: string
  accountabilityNotes: string
  daysPerWeek: number
}

export type Student = {
  id: string
  name: string
  age: number
  gradeLevel: string
  targetGradYear: string
  learningStyle: string
  strengths: string
  friction: string
  interests: string[]
  collegeDirection: string
  weeklyCapacity: number
  priorities: string[]
}

export type Course = {
  id: string
  studentId: string
  title: string
  subject: SubjectArea
  creditGoal: number
  weeklyHours: number
  level: 'foundation' | 'standard' | 'honors' | 'ap-de-ready'
  status: CourseStatus
  why: string
  skills: string[]
  outputs: string[]
  resources: string[]
}

export type LearningModule = {
  id: string
  courseId: string
  title: string
  weeks: number
  drivingQuestion: string
  deliverable: string
  status: ModuleStatus
}

export type WeeklyPlan = {
  id: string
  weekOf: string
  studentId: string
  focus: string
  sessions: LearningSession[]
}

export type LearningSession = {
  id: string
  courseId: string
  date: string
  durationMinutes: number
  task: string
  status: SessionStatus
  evidence: string
  notes: string
}

export type InterviewAnswer = {
  id: string
  prompt: string
  answer: string
  category: 'legal' | 'profile' | 'college' | 'pedagogy' | 'operations'
}

export type AppState = {
  school: SchoolProfile
  students: Student[]
  courses: Course[]
  modules: LearningModule[]
  weeklyPlans: WeeklyPlan[]
  interview: InterviewAnswer[]
}
