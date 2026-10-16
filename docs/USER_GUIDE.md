# NexusThesis User Guide

Looking for installation instructions? See the [Setup Guide](SETUP.md) instead. Back to the [main page](../README.md).

## Who uses it?

| Role           | Who they are                                      | What they do in the system                                                                 |
| -------------- | ------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| **Student**    | A postgraduate student                            | Submits a research proposal, uploads thesis drafts and the final thesis, follows progress. |
| **Supervisor** | A lecturer assigned to guide a particular student | Reviews and approves the proposal and supervision milestones, and endorses the thesis.     |
| **Examiner**   | A lecturer assigned to assess a student's thesis  | Reads the thesis and writes a confidential evaluation.                                     |
| **Admin**      | The system administrator                          | Manages user accounts and looks after the system.                                          |

Supervisors and examiners are both lecturers. A lecturer's role depends on which students they are assigned to. The same lecturer can supervise one student and examine another, but never both for the same student.

## How a thesis moves through the system

1. **Proposal.** The student submits a research proposal.
2. **Supervisor approval.** The supervisor reviews the proposal and the supervision milestones along the way.
3. **Thesis upload.** The student uploads the thesis.
4. **Supervisor endorsement.** The supervisor confirms the thesis is ready to be examined.
5. **Examination.** The examiner reads the thesis and submits an evaluation.
6. **Final approval.** The result is recorded. If changes are needed, the student revises and resubmits.

The system keeps a record of important actions, such as approvals, so there is a clear history of what happened and when.

## Trying a demo

If you need a demo set up, follow the [Setup Guide](SETUP.md).

> **Demo only:** these accounts are fake and use a shared password. They exist for testing. Real users should never share a password.

All demo accounts use the password `Password123!1`.

| Role      | Email                                                         |
| --------- | ------------------------------------------------------------- |
| Admin     | `admin@nexusthesis.com`                                       |
| Lecturers | `lecturer01@nexusthesis.com` up to `lecturer20@nexusthesis.com` |
| Students  | `student01@nexusthesis.com` up to `student10@nexusthesis.com`   |

Some students already have a thesis linked to a supervisor and examiner, so you can see what each role's view looks like:

| Student    | Thesis                                            | Supervisor  | Examiner    |
| ---------- | ------------------------------------------------- | ----------- | ----------- |
| Student 01 | Development of Role-Based Access System           | Lecturer 01 | Lecturer 02 |
| Student 02 | Analysis of Automated CI/CD Pipelines             | Lecturer 02 | Lecturer 01 |
| Student 03 | Secure Local Network Routing via WireGuard        | Lecturer 03 | Lecturer 04 |
| Student 04 | Optimizing SQLite Performance in Web Applications | Lecturer 04 | Lecturer 03 |
| Student 05 | Automated Document Plagiarism Detection Framework | Lecturer 05 | Lecturer 01 |

Students 06 to 10 have no thesis yet, so you can use them to try submitting a new proposal from scratch.
