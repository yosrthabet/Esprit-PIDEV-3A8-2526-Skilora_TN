-- Finance Module: Add hire_offer_id FK + dispute fields to contracts table
-- Run this manually: mysql -u root skilora < migrations/finance_contract_upgrade.sql

ALTER TABLE contracts
    ADD COLUMN hire_offer_id INT DEFAULT NULL AFTER status,
    ADD COLUMN dispute_reason LONGTEXT DEFAULT NULL AFTER hire_offer_id,
    ADD COLUMN dispute_resolution LONGTEXT DEFAULT NULL AFTER dispute_reason;

ALTER TABLE contracts
    ADD CONSTRAINT FK_contracts_hire_offer
    FOREIGN KEY (hire_offer_id) REFERENCES hire_offers(id) ON DELETE SET NULL;

CREATE INDEX IDX_contracts_hire_offer ON contracts (hire_offer_id);

-- Invoice table
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) NOT NULL UNIQUE,
    contract_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    company_id INT DEFAULT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'CONNECTS',
    status VARCHAR(20) NOT NULL DEFAULT 'DRAFT',
    description LONGTEXT DEFAULT NULL,
    issued_date DATETIME NOT NULL,
    paid_date DATETIME DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    CONSTRAINT FK_invoices_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    CONSTRAINT FK_invoices_freelancer FOREIGN KEY (freelancer_id) REFERENCES users(id),
    CONSTRAINT FK_invoices_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    INDEX IDX_invoices_freelancer (freelancer_id),
    INDEX IDX_invoices_company (company_id),
    INDEX IDX_invoices_contract (contract_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bonus → Milestone: add contract_id and status
ALTER TABLE bonuses
    ADD COLUMN contract_id INT DEFAULT NULL AFTER user_id,
    ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER date_awarded;

ALTER TABLE bonuses
    ADD CONSTRAINT FK_bonuses_contract
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL;

CREATE INDEX IDX_bonuses_contract ON bonuses (contract_id);
