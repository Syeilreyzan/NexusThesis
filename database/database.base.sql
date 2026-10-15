-- Hardened SQLite schema (v2) for the thesis management system
-- Usage: sqlite3 database.sqlite < schema_v2.sql
--
-- Designed for a FRESH database. CREATE TABLE IF NOT EXISTS does not alter
-- tables that already exist, so if you already loaded schema.sql (v1) and
-- have no data you need, either delete database.sqlite or uncomment the
-- reset block below (DESTRUCTIVE: drops all five tables and their data).
--
-- NOTE: foreign key enforcement is per-connection in SQLite.
--       Run PRAGMA foreign_keys = ON; in your app's connection too.

-- ---- OPTIONAL RESET (destructive) -----------------------------------
-- PRAGMA foreign_keys = OFF;
-- DROP TABLE IF EXISTS audit_logs;
-- DROP TABLE IF EXISTS evaluations;
-- DROP TABLE IF EXISTS thesis_documents;
-- DROP TABLE IF EXISTS theses;
-- DROP TABLE IF EXISTS users;
-- ---------------------------------------------------------------------

PRAGMA foreign_keys = ON;

BEGIN TRANSACTION;

-- ---------------------------------------------------------------
-- USERS
-- Users are soft-deleted (deleted_at) and never hard-deleted, so that
-- theses, documents, evaluations and the audit trail keep their owner.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    email       TEXT NOT NULL UNIQUE COLLATE NOCASE,   -- case-insensitive uniqueness
    password    TEXT NOT NULL,                         -- Argon2id/bcrypt hash only
    role        TEXT NOT NULL CHECK (role IN ('student', 'lecturer', 'admin')),
    deleted_at  TEXT,                                  -- soft delete
    created_at  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP)
);

-- ---------------------------------------------------------------
-- THESES
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS theses (
    id                          INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id                  INTEGER NOT NULL,
    supervisor_id               INTEGER,
    examiner_id                 INTEGER,
    title                       TEXT NOT NULL,
    abstract                    TEXT,
    status                      TEXT NOT NULL DEFAULT 'draft' CHECK (status IN (
                                    'draft',
                                    'proposal_submitted',
                                    'sv_approved',
                                    'candidature_approved',
                                    'thesis_uploaded',
                                    'sv_endorsed',
                                    'examined',
                                    'final_approved',
                                    'revision_needed'
                                )),
    examiner_access_expires_at  TEXT,   -- time-limited examiner access (enforce in app on every request)
    deleted_at                  TEXT,   -- soft delete
    created_at                  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at                  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),

    -- Conflict of interest: supervisor and examiner must be different people
    CHECK (supervisor_id IS NULL OR examiner_id IS NULL OR supervisor_id <> examiner_id),

    FOREIGN KEY (student_id)    REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (examiner_id)   REFERENCES users(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_theses_student_id    ON theses(student_id);
CREATE INDEX IF NOT EXISTS idx_theses_supervisor_id ON theses(supervisor_id);
CREATE INDEX IF NOT EXISTS idx_theses_examiner_id   ON theses(examiner_id);
CREATE INDEX IF NOT EXISTS idx_theses_status        ON theses(status);

-- Role validation: student_id must be a student; supervisor/examiner must be lecturers.
CREATE TRIGGER IF NOT EXISTS trg_theses_roles_insert
BEFORE INSERT ON theses
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'theses.student_id must reference a user with role student')
    WHERE (SELECT role FROM users WHERE id = NEW.student_id) IS NOT 'student';

    SELECT RAISE(ABORT, 'theses.supervisor_id must reference a user with role lecturer')
    WHERE NEW.supervisor_id IS NOT NULL
      AND (SELECT role FROM users WHERE id = NEW.supervisor_id) IS NOT 'lecturer';

    SELECT RAISE(ABORT, 'theses.examiner_id must reference a user with role lecturer')
    WHERE NEW.examiner_id IS NOT NULL
      AND (SELECT role FROM users WHERE id = NEW.examiner_id) IS NOT 'lecturer';
END;

CREATE TRIGGER IF NOT EXISTS trg_theses_roles_update
BEFORE UPDATE OF student_id, supervisor_id, examiner_id ON theses
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'theses.student_id must reference a user with role student')
    WHERE (SELECT role FROM users WHERE id = NEW.student_id) IS NOT 'student';

    SELECT RAISE(ABORT, 'theses.supervisor_id must reference a user with role lecturer')
    WHERE NEW.supervisor_id IS NOT NULL
      AND (SELECT role FROM users WHERE id = NEW.supervisor_id) IS NOT 'lecturer';

    SELECT RAISE(ABORT, 'theses.examiner_id must reference a user with role lecturer')
    WHERE NEW.examiner_id IS NOT NULL
      AND (SELECT role FROM users WHERE id = NEW.examiner_id) IS NOT 'lecturer';
END;

-- ---------------------------------------------------------------
-- THESIS_DOCUMENTS
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS thesis_documents (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    thesis_id        INTEGER NOT NULL,
    uploaded_by      INTEGER NOT NULL,
    document_type    TEXT NOT NULL CHECK (document_type IN ('proposal', 'progress_report', 'final_thesis')),
    original_name    TEXT NOT NULL,   -- display only; never use in filesystem paths
    storage_path     TEXT NOT NULL UNIQUE,   -- server-generated UUID path outside public root
    mime_type        TEXT NOT NULL DEFAULT 'application/pdf' CHECK (mime_type = 'application/pdf'),
    file_size        INTEGER NOT NULL CHECK (file_size > 0 AND file_size <= 10485760),  -- bytes, max 10MB
    file_hash        TEXT NOT NULL,   -- SHA-256 for integrity
    similarity_score INTEGER CHECK (similarity_score IS NULL OR similarity_score BETWEEN 0 AND 100),
    created_at       TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at       TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),

    FOREIGN KEY (thesis_id)   REFERENCES theses(id) ON DELETE RESTRICT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)  ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_thesis_documents_thesis_id   ON thesis_documents(thesis_id);
CREATE INDEX IF NOT EXISTS idx_thesis_documents_uploaded_by ON thesis_documents(uploaded_by);
CREATE INDEX IF NOT EXISTS idx_thesis_documents_file_hash   ON thesis_documents(file_hash);

-- ---------------------------------------------------------------
-- EVALUATIONS
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evaluations (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    thesis_id        INTEGER NOT NULL,
    reviewer_id      INTEGER NOT NULL,
    role_context     TEXT NOT NULL CHECK (role_context IN ('supervisor', 'examiner')),
    comments         TEXT,   -- store raw; escape on output for the right context
    verdict          TEXT NOT NULL CHECK (verdict IN ('approve', 'revise', 'pass', 'fail')),
    is_confidential  INTEGER NOT NULL DEFAULT 0 CHECK (is_confidential IN (0, 1)),
    created_at       TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
    updated_at       TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),

    -- Examiner reports are always confidential
    CHECK (role_context <> 'examiner' OR is_confidential = 1),

    FOREIGN KEY (thesis_id)   REFERENCES theses(id) ON DELETE RESTRICT,
    FOREIGN KEY (reviewer_id) REFERENCES users(id)  ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_evaluations_thesis_id   ON evaluations(thesis_id);
CREATE INDEX IF NOT EXISTS idx_evaluations_reviewer_id ON evaluations(reviewer_id);

-- The reviewer must be the supervisor/examiner actually assigned to that thesis.
CREATE TRIGGER IF NOT EXISTS trg_evaluations_reviewer_insert
BEFORE INSERT ON evaluations
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'reviewer is not the assigned supervisor/examiner for this thesis')
    WHERE NOT EXISTS (
        SELECT 1 FROM theses t
        WHERE t.id = NEW.thesis_id
          AND (   (NEW.role_context = 'supervisor' AND t.supervisor_id = NEW.reviewer_id)
               OR (NEW.role_context = 'examiner'   AND t.examiner_id   = NEW.reviewer_id))
    );
END;

CREATE TRIGGER IF NOT EXISTS trg_evaluations_reviewer_update
BEFORE UPDATE OF thesis_id, reviewer_id, role_context ON evaluations
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'reviewer is not the assigned supervisor/examiner for this thesis')
    WHERE NOT EXISTS (
        SELECT 1 FROM theses t
        WHERE t.id = NEW.thesis_id
          AND (   (NEW.role_context = 'supervisor' AND t.supervisor_id = NEW.reviewer_id)
               OR (NEW.role_context = 'examiner'   AND t.examiner_id   = NEW.reviewer_id))
    );
END;

-- ---------------------------------------------------------------
-- AUDIT_LOGS (append-only)
-- No updated_at, and UPDATE/DELETE are blocked by triggers.
-- user_id is RESTRICT (not SET NULL) because users are soft-deleted,
-- and SET NULL would be an UPDATE that the immutability trigger blocks.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER,            -- nullable (e.g. unauthenticated attempts)
    action       TEXT NOT NULL,      -- e.g. UNAUTHORIZED_ACCESS_ATTEMPT, FINAL_APPROVAL
    target_type  TEXT,               -- e.g. App\Models\Thesis
    target_id    INTEGER,
    ip_address   TEXT,
    created_at   TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action  ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_logs_target  ON audit_logs(target_type, target_id);

CREATE TRIGGER IF NOT EXISTS trg_audit_logs_no_update
BEFORE UPDATE ON audit_logs
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'audit_logs is append-only');
END;

CREATE TRIGGER IF NOT EXISTS trg_audit_logs_no_delete
BEFORE DELETE ON audit_logs
FOR EACH ROW
BEGIN
    SELECT RAISE(ABORT, 'audit_logs is append-only');
END;

-- ---------------------------------------------------------------
-- updated_at auto-maintenance (SQLite has no ON UPDATE CURRENT_TIMESTAMP)
-- ---------------------------------------------------------------
CREATE TRIGGER IF NOT EXISTS trg_users_updated_at
AFTER UPDATE ON users
FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_theses_updated_at
AFTER UPDATE ON theses
FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE theses SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_thesis_documents_updated_at
AFTER UPDATE ON thesis_documents
FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE thesis_documents SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_evaluations_updated_at
AFTER UPDATE ON evaluations
FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE evaluations SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

COMMIT;
