-- Table: 0_ksf_rbac_roles - Access roles (Module x Permission x Scope)
CREATE TABLE IF NOT EXISTS 0_ksf_rbac_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(50) NOT NULL,
    permission VARCHAR(20) NOT NULL,  -- View, Create, Edit, Delete
    scope VARCHAR(10) NOT NULL,      -- None, Mine, Team, All
    record_type VARCHAR(50) NULL,
    record_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: 0_ksf_hrm_position_roles - Link HRM positions to access roles
CREATE TABLE IF NOT EXISTS 0_ksf_hrm_position_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    access_role_id INT NOT NULL,
    FOREIGN KEY (position_id) REFERENCES hrm_positions(id),
    FOREIGN KEY (access_role_id) REFERENCES rbac_roles(id)
);

-- Table: 0_ksf_hrm_employee_positions - Track employee position history
CREATE TABLE IF NOT EXISTS 0_ksf_hrm_employee_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    position_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    FOREIGN KEY (employee_id) REFERENCES hrm_employees(id),
    FOREIGN KEY (position_id) REFERENCES hrm_positions(id)
);

-- Table: 0_ksf_rbac_employee_roles - Direct RBAC role assignment to employees (overrides inherited roles)
CREATE TABLE IF NOT EXISTS 0_ksf_rbac_employee_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES hrm_employees(id),
    FOREIGN KEY (role_id) REFERENCES 0_ksf_rbac_roles(id)
);

-- Sample access roles for CRM module
INSERT INTO 0_ksf_rbac_roles (module, permission, scope, record_type, record_id) VALUES
    ('CRM', 'View', 'All', 'customer', NULL),
    ('CRM', 'Create', 'All', 'customer', NULL),
    ('CRM', 'Edit', 'All', 'customer', NULL),
    ('CRM', 'Delete', 'All', 'customer', NULL),
    ('CRM', 'View', 'Mine', 'customer', NULL),
    ('CRM', 'Create', 'Mine', 'customer', NULL),
    ('CRM', 'Edit', 'Mine', 'customer', NULL),
    ('CRM', 'Delete', 'Mine', 'customer', NULL),
    ('CRM', 'View', 'Team', 'customer', NULL),
    ('CRM', 'Create', 'Team', 'customer', NULL),
    ('CRM', 'Edit', 'Team', 'customer', NULL),
    ('CRM', 'Delete', 'Team', 'customer', NULL);

-- Sample position-role mappings
INSERT INTO 0_ksf_hrm_position_roles (position_id, access_role_id) VALUES
    (1, 1),  -- Position 1 gets CRM View/Create/Edit/Delete
    (2, 2),  -- Position 2 gets CRM View/Mine/Team/All
    (3, 3),  -- Position 3 gets CRM View/Create/Edit/Delete
    (4, 4),  -- Position 4 gets CRM View/Create/Edit/Delete
    (5, 5),  -- Position 5 gets CRM View/Create/Edit/Delete
    (6, 6),  -- Position 6 gets CRM View/Create/Edit/Delete;

-- Sample employee-position mappings
INSERT INTO 0_ksf_hrm_employee_positions (employee_id, position_id, start_date) VALUES
    (101, 1, '2020-01-15'),
    (102, 2, '2020-02-01'),
    (103, 3, '2020-03-01');
