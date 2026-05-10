# Northstar Homeschool Assistant

A private, local-first planning app for designing, executing, and tracking a college-bound homeschool curriculum for two learners.

## What it does

- Stores learner profiles, family school profile, and a guided planning interview.
- Builds editable courses with credits, weekly hours, levels, outcomes, skills, resources, and modules.
- Generates six-week curriculum sprints and weekly execution sessions.
- Tracks completed work, evidence notes, transcript snapshots, credit progress, and college-file readiness.
- Exports and imports the full plan as JSON.
- Prints a planning snapshot from the browser.

## Run locally

```bash
npm install
npm run dev
```

The app stores data in browser `localStorage`; no student data is sent to a server.

## Planning Sources

The college-prep defaults are intentionally editable. They use these current public references as planning anchors, not legal advice:

- College Board BigFuture: https://bigfuture.collegeboard.org/plan-for-college/stand-out-in-high-school/high-school-classes-colleges-look-for
- UC A-G subject requirements: https://admission.universityofcalifornia.edu/admission-requirements/first-year-requirements/subject-requirement-a-g.html
- Common App counselor and recommender resources: https://www.commonapp.org/counselors-and-recommenders/
- NCAA homeschool student guidance: https://www.ncaa.org/sports/2014/10/13/home-school-students.aspx
