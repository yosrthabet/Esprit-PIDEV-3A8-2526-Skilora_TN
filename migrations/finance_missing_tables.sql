-- ============================================================================
-- Finance Module: Create 6 missing tables
-- These entities use finance_* prefixed table names but were never migrated.
-- Run: mysql -u root skilorafinal < migrations/finance_missing_tables.sql
-- ============================================================================

-- 1. finance_wallets (Entity: Wallet)
CREATE TABLE IF NOT EXISTS finance_wallets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    balance     DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    currency    VARCHAR(3) NOT NULL DEFAULT 'TND',
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    UNIQUE INDEX uniq_finance_wallet_user_currency (user_id, currency),
    CONSTRAINT FK_finance_wallets_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. finance_bank_accounts (Entity: BankAccount)
CREATE TABLE IF NOT EXISTS finance_bank_accounts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    bank_name       VARCHAR(120) NOT NULL,
    account_holder  VARCHAR(160) NOT NULL,
    iban            VARCHAR(64) NOT NULL,
    swift           VARCHAR(20) DEFAULT NULL,
    is_primary      TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL,
    INDEX IDX_finance_bank_accounts_user (user_id),
    CONSTRAINT FK_finance_bank_accounts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. finance_payslips (Entity: Payslip)
CREATE TABLE IF NOT EXISTS finance_payslips (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    employee_id  INT NOT NULL,
    period_start DATE NOT NULL,
    period_end   DATE NOT NULL,
    gross_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    net_amount   DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    currency     VARCHAR(3) NOT NULL DEFAULT 'TND',
    status       VARCHAR(20) NOT NULL DEFAULT 'issued',
    created_at   DATETIME NOT NULL,
    INDEX IDX_finance_payslips_employee (employee_id),
    CONSTRAINT FK_finance_payslips_employee FOREIGN KEY (employee_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. finance_payment_transactions (Entity: PaymentTransaction)
CREATE TABLE IF NOT EXISTS finance_payment_transactions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    type                VARCHAR(40) NOT NULL DEFAULT 'topup',
    amount              DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    currency            VARCHAR(3) NOT NULL DEFAULT 'TND',
    status              VARCHAR(30) NOT NULL DEFAULT 'succeeded',
    provider_reference  VARCHAR(120) DEFAULT NULL,
    created_at          DATETIME NOT NULL,
    INDEX idx_finance_payment_user_created (user_id, created_at),
    CONSTRAINT FK_finance_payment_transactions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. finance_exchange_rates (Entity: ExchangeRate)
CREATE TABLE IF NOT EXISTS finance_exchange_rates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    base_currency   VARCHAR(3) NOT NULL DEFAULT 'TND',
    quote_currency  VARCHAR(3) NOT NULL DEFAULT 'EUR',
    rate            DECIMAL(12, 6) NOT NULL DEFAULT 1.000000,
    effective_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. finance_contract_milestones (Entity: ContractMilestone)
CREATE TABLE IF NOT EXISTS finance_contract_milestones (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    contract_id  INT NOT NULL,
    title        VARCHAR(180) NOT NULL,
    description  LONGTEXT DEFAULT NULL,
    amount       DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    currency     VARCHAR(3) NOT NULL DEFAULT 'TND',
    status       VARCHAR(20) NOT NULL DEFAULT 'pending',
    due_at       DATETIME DEFAULT NULL,
    created_at   DATETIME NOT NULL,
    INDEX IDX_finance_contract_milestones_contract (contract_id),
    CONSTRAINT FK_finance_contract_milestones_contract FOREIGN KEY (contract_id) REFERENCES finance_contracts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
