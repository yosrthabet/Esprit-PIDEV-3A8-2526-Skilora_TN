<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502140923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY `applications_ibfk_2`');
        $this->addSql('DROP INDEX idx_applications_candidate_id ON applications');
        $this->addSql('DROP INDEX idx_applications_status ON applications');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY `applications_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            CHANGE
              candidate_id candidate_id INT NOT NULL,
            CHANGE
              applied_date applied_date DATETIME DEFAULT NULL,
            CHANGE
              applied_at applied_at DATETIME NOT NULL,
            CHANGE
              cover_letter cover_letter LONGTEXT DEFAULT NULL,
            CHANGE
              custom_cv_url custom_cv_url LONGTEXT DEFAULT NULL,
            CHANGE
              match_percentage match_percentage NUMERIC(5, 2) DEFAULT NULL,
            CHANGE
              candidate_score candidate_score INT DEFAULT NULL,
            CHANGE
              cv_path cv_path VARCHAR(500) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            ADD
              CONSTRAINT FK_F7C966F091BD8781 FOREIGN KEY (candidate_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX IDX_F7C966F091BD8781 ON applications (candidate_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_application_candidate ON applications (job_offer_id, candidate_id)');
        $this->addSql('DROP INDEX idx_applications_job_offer_id ON applications');
        $this->addSql('CREATE INDEX IDX_F7C966F03481D195 ON applications (job_offer_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            ADD
              CONSTRAINT `applications_ibfk_1` FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE bank_accounts DROP FOREIGN KEY `bank_accounts_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bank_accounts
            DROP
              account_holder,
            DROP
              swift_bic,
            DROP
              rib,
            DROP
              created_date,
            CHANGE
              iban iban VARCHAR(50) DEFAULT NULL,
            CHANGE
              currency currency VARCHAR(10) DEFAULT NULL,
            CHANGE
              is_primary is_primary TINYINT DEFAULT 0 NOT NULL,
            CHANGE
              is_verified is_verified TINYINT DEFAULT 0 NOT NULL,
            CHANGE
              swift swift VARCHAR(20) DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_bank_accounts_user ON bank_accounts');
        $this->addSql('CREATE INDEX idx_bank_accounts_user_id ON bank_accounts (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bank_accounts
            ADD
              CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_blog_published ON blog_articles');
        $this->addSql('ALTER TABLE blog_articles DROP FOREIGN KEY `blog_articles_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              blog_articles
            CHANGE
              title title VARCHAR(255) NOT NULL,
            CHANGE
              content content LONGTEXT NOT NULL,
            CHANGE
              summary summary LONGTEXT DEFAULT NULL,
            CHANGE
              cover_image_url cover_image_url VARCHAR(2048) DEFAULT NULL,
            CHANGE
              category category VARCHAR(100) DEFAULT NULL,
            CHANGE
              views_count views_count INT DEFAULT 0 NOT NULL,
            CHANGE
              likes_count likes_count INT DEFAULT 0 NOT NULL,
            CHANGE
              is_published is_published TINYINT DEFAULT 0 NOT NULL,
            CHANGE
              created_date created_date DATETIME NOT NULL,
            CHANGE
              updated_date updated_date DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_blog_author ON blog_articles');
        $this->addSql('CREATE INDEX IDX_CB80154FF675F31B ON blog_articles (author_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              blog_articles
            ADD
              CONSTRAINT `blog_articles_ibfk_1` FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE bonuses DROP FOREIGN KEY `bonuses_ibfk_1`');
        $this->addSql('ALTER TABLE bonuses DROP created_date, CHANGE amount amount DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('DROP INDEX idx_bonuses_user ON bonuses');
        $this->addSql('CREATE INDEX IDX_8535CFD2A76ED395 ON bonuses (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bonuses
            ADD
              CONSTRAINT `bonuses_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE certificates DROP FOREIGN KEY `certificates_ibfk_1`');
        $this->addSql('DROP INDEX enrollment_id ON certificates');
        $this->addSql('DROP INDEX certificate_number ON certificates');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              certificates
            DROP
              enrollment_id,
            DROP
              certificate_number,
            DROP
              qr_code,
            DROP
              hash_value,
            DROP
              pdf_url,
            DROP
              verification_token,
            DROP
              completed_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              certificates
            ADD
              CONSTRAINT FK_8D26FB5FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              certificates
            ADD
              CONSTRAINT FK_8D26FB5F5200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX IDX_8D26FB5FA76ED395 ON certificates (user_id)');
        $this->addSql('CREATE INDEX IDX_8D26FB5F5200282E ON certificates (formation_id)');
        $this->addSql('DROP INDEX uniq_certificate_verification_id ON certificates');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D26FB5F1623CB0A ON certificates (verification_id)');
        $this->addSql('ALTER TABLE community_events DROP FOREIGN KEY `FK_community_event_organizer`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_events
            CHANGE
              event_type event_type VARCHAR(30) NOT NULL,
            CHANGE
              start_date start_date DATETIME NOT NULL,
            CHANGE
              end_date end_date DATETIME DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_community_event_organizer ON community_events');
        $this->addSql('CREATE INDEX IDX_224DA2B9876C4DDA ON community_events (organizer_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_events
            ADD
              CONSTRAINT `FK_community_event_organizer` FOREIGN KEY (organizer_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE community_groups DROP FOREIGN KEY `community_groups_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_groups
            CHANGE
              name name VARCHAR(255) NOT NULL,
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              category category VARCHAR(100) DEFAULT NULL,
            CHANGE
              cover_image_url cover_image_url VARCHAR(2048) DEFAULT NULL,
            CHANGE
              member_count member_count INT DEFAULT 1 NOT NULL,
            CHANGE
              is_public is_public TINYINT DEFAULT 1 NOT NULL,
            CHANGE
              created_date created_date DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX creator_id ON community_groups');
        $this->addSql('CREATE INDEX IDX_81A7CC8361220EA6 ON community_groups (creator_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_groups
            ADD
              CONSTRAINT `community_groups_ibfk_1` FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE community_notifications DROP FOREIGN KEY `FK_community_notification_user`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_notifications
            CHANGE
              type type VARCHAR(50) NOT NULL,
            CHANGE
              icon icon VARCHAR(10) DEFAULT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_community_notification_user ON community_notifications');
        $this->addSql('CREATE INDEX IDX_EF2FBEA76ED395 ON community_notifications (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_notifications
            ADD
              CONSTRAINT `FK_community_notification_user` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE community_posts DROP FOREIGN KEY `fk_community_posts_author`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_posts
            CHANGE
              content content LONGTEXT NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL,
            CHANGE
              updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX fk_community_posts_author ON community_posts');
        $this->addSql('CREATE INDEX IDX_F32DC0BEF675F31B ON community_posts (author_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_posts
            ADD
              CONSTRAINT `fk_community_posts_author` FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY `companies_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              companies
            CHANGE
              logo_url logo_url LONGTEXT DEFAULT NULL,
            CHANGE
              is_verified is_verified TINYINT DEFAULT 0 NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_companies_owner ON companies');
        $this->addSql('CREATE INDEX IDX_8244AA3A7E3C61F9 ON companies (owner_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              companies
            ADD
              CONSTRAINT `companies_ibfk_1` FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('DROP INDEX idx_contracts_status ON contracts');
        $this->addSql('ALTER TABLE contracts DROP FOREIGN KEY `contracts_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contracts
            DROP
              created_date,
            CHANGE
              type type VARCHAR(50) DEFAULT NULL,
            CHANGE
              position position VARCHAR(100) DEFAULT NULL,
            CHANGE
              salary salary DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              status status VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contracts
            ADD
              CONSTRAINT FK_950A973BD7CCBD6 FOREIGN KEY (company_Name) REFERENCES companies (id)
        SQL);
        $this->addSql('CREATE INDEX IDX_950A973BD7CCBD6 ON contracts (company_Name)');
        $this->addSql('DROP INDEX idx_contracts_user ON contracts');
        $this->addSql('CREATE INDEX IDX_950A973A76ED395 ON contracts (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contracts
            ADD
              CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE dm_conversations DROP FOREIGN KEY `fk_dm_conv_high`');
        $this->addSql('ALTER TABLE dm_conversations CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX fk_dm_conv_high ON dm_conversations');
        $this->addSql('CREATE INDEX IDX_3429FA2DC4324F5 ON dm_conversations (participant_high_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_conversations
            ADD
              CONSTRAINT `fk_dm_conv_high` FOREIGN KEY (participant_high_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE dm_messages DROP FOREIGN KEY `fk_dm_msg_conv`');
        $this->addSql('ALTER TABLE dm_messages DROP FOREIGN KEY `fk_dm_msg_sender`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_messages
            CHANGE
              body body LONGTEXT NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL,
            CHANGE
              updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX fk_dm_msg_conv ON dm_messages');
        $this->addSql('CREATE INDEX IDX_BC5894CE9AC0396 ON dm_messages (conversation_id)');
        $this->addSql('DROP INDEX fk_dm_msg_sender ON dm_messages');
        $this->addSql('CREATE INDEX IDX_BC5894CEF624B39D ON dm_messages (sender_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_messages
            ADD
              CONSTRAINT `fk_dm_msg_conv` FOREIGN KEY (conversation_id) REFERENCES dm_conversations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_messages
            ADD
              CONSTRAINT `fk_dm_msg_sender` FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_escrow_status ON escrow_accounts');
        $this->addSql('ALTER TABLE escrow_accounts DROP FOREIGN KEY `escrow_accounts_ibfk_2`');
        $this->addSql('ALTER TABLE escrow_accounts DROP FOREIGN KEY `FK_ESCROW_CONTRACT`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              escrow_accounts
            CHANGE
              currency currency VARCHAR(10) DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT NULL,
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT NULL,
            CHANGE
              release_notes release_notes LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_escrow_contract ON escrow_accounts');
        $this->addSql('CREATE INDEX IDX_9E94F4972576E0FD ON escrow_accounts (contract_id)');
        $this->addSql('DROP INDEX idx_escrow_admin ON escrow_accounts');
        $this->addSql('CREATE INDEX IDX_9E94F497642B8210 ON escrow_accounts (admin_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              escrow_accounts
            ADD
              CONSTRAINT `escrow_accounts_ibfk_2` FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              escrow_accounts
            ADD
              CONSTRAINT `FK_ESCROW_CONTRACT` FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE event_rsvps DROP FOREIGN KEY `event_rsvps_ibfk_2`');
        $this->addSql('ALTER TABLE event_rsvps DROP FOREIGN KEY `FK_event_rsvps_event`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_rsvps
            CHANGE
              status status VARCHAR(20) NOT NULL,
            CHANGE
              rsvp_date rsvp_date DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_event_rsvps_event ON event_rsvps');
        $this->addSql('CREATE INDEX IDX_CBEEB77E71F7E88B ON event_rsvps (event_id)');
        $this->addSql('DROP INDEX user_id ON event_rsvps');
        $this->addSql('CREATE INDEX IDX_CBEEB77EA76ED395 ON event_rsvps (user_id)');
        $this->addSql('DROP INDEX uq_rsvp ON event_rsvps');
        $this->addSql('CREATE UNIQUE INDEX unique_event_user_rsvp ON event_rsvps (event_id, user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_rsvps
            ADD
              CONSTRAINT `event_rsvps_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_rsvps
            ADD
              CONSTRAINT `FK_event_rsvps_event` FOREIGN KEY (event_id) REFERENCES community_events (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX uq_rate ON exchange_rates');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              exchange_rates
            CHANGE
              source source VARCHAR(50) DEFAULT NULL,
            CHANGE
              last_updated last_updated DATETIME DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE experiences DROP FOREIGN KEY `experiences_ibfk_1`');
        $this->addSql('ALTER TABLE experiences DROP FOREIGN KEY `experiences_ibfk_1`');
        $this->addSql('ALTER TABLE experiences CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              experiences
            ADD
              CONSTRAINT FK_82020E70CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql('DROP INDEX idx_experiences_profile_id ON experiences');
        $this->addSql('CREATE INDEX IDX_82020E70CCFA12B8 ON experiences (profile_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              experiences
            ADD
              CONSTRAINT `experiences_ibfk_1` FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formation_enrollments DROP FOREIGN KEY `fk_enrollment_formation`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_enrollments
            CHANGE
              enrolled_at enrolled_at DATETIME NOT NULL,
            CHANGE
              completed_at completed_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX fk_enrollment_formation ON formation_enrollments');
        $this->addSql('CREATE INDEX IDX_9325E1E95200282E ON formation_enrollments (formation_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_enrollments
            ADD
              CONSTRAINT `fk_enrollment_formation` FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formation_materials DROP FOREIGN KEY `formation_materials_ibfk_1`');
        $this->addSql('ALTER TABLE formation_materials DROP FOREIGN KEY `formation_materials_ibfk_2`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_materials
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              file_url file_url LONGTEXT DEFAULT NULL,
            CHANGE
              file_size file_size BIGINT DEFAULT NULL,
            CHANGE
              download_count download_count INT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_fmat_formation ON formation_materials');
        $this->addSql('CREATE INDEX IDX_516A46685200282E ON formation_materials (formation_id)');
        $this->addSql('DROP INDEX uploaded_by ON formation_materials');
        $this->addSql('CREATE INDEX IDX_516A4668E3E73126 ON formation_materials (uploaded_by)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_materials
            ADD
              CONSTRAINT `formation_materials_ibfk_1` FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_materials
            ADD
              CONSTRAINT `formation_materials_ibfk_2` FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('ALTER TABLE formation_modules DROP FOREIGN KEY `formation_modules_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_modules
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              content_url content_url LONGTEXT DEFAULT NULL,
            CHANGE
              duration_minutes duration_minutes INT DEFAULT NULL,
            CHANGE
              order_index order_index INT DEFAULT NULL,
            CHANGE
              content content LONGTEXT DEFAULT NULL,
            CHANGE
              created_at created_at DATETIME DEFAULT NULL,
            CHANGE
              updated_at updated_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX formation_id ON formation_modules');
        $this->addSql('CREATE INDEX IDX_6B4806AC5200282E ON formation_modules (formation_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_modules
            ADD
              CONSTRAINT `formation_modules_ibfk_1` FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formation_review_likes DROP FOREIGN KEY `FK_review_likes_review`');
        $this->addSql('ALTER TABLE formation_review_likes DROP FOREIGN KEY `FK_review_likes_user`');
        $this->addSql('DROP INDEX idx_review_likes_review ON formation_review_likes');
        $this->addSql('CREATE INDEX IDX_4B1A941A3E2E969B ON formation_review_likes (review_id)');
        $this->addSql('DROP INDEX idx_review_likes_user ON formation_review_likes');
        $this->addSql('CREATE INDEX IDX_4B1A941AA76ED395 ON formation_review_likes (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_review_likes
            ADD
              CONSTRAINT `FK_review_likes_review` FOREIGN KEY (review_id) REFERENCES formation_reviews (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_review_likes
            ADD
              CONSTRAINT `FK_review_likes_user` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY `FK_formation_reviews_formation`');
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY `FK_formation_reviews_user`');
        $this->addSql('ALTER TABLE formation_reviews CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_formation_reviews_formation ON formation_reviews');
        $this->addSql('CREATE INDEX IDX_2C8FAE745200282E ON formation_reviews (formation_id)');
        $this->addSql('DROP INDEX fk_formation_reviews_user ON formation_reviews');
        $this->addSql('CREATE INDEX IDX_2C8FAE74A76ED395 ON formation_reviews (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_reviews
            ADD
              CONSTRAINT `FK_formation_reviews_formation` FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_reviews
            ADD
              CONSTRAINT `FK_formation_reviews_user` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formations DROP FOREIGN KEY `formations_ibfk_1`');
        $this->addSql('DROP INDEX idx_formations_status ON formations');
        $this->addSql('DROP INDEX created_by ON formations');
        $this->addSql('DROP INDEX idx_formations_category ON formations');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formations
            CHANGE
              title title VARCHAR(255) NOT NULL,
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              category category VARCHAR(64) NOT NULL,
            CHANGE
              duration_hours duration_hours INT DEFAULT NULL,
            CHANGE
              cost cost NUMERIC(10, 2) DEFAULT NULL,
            CHANGE
              currency currency VARCHAR(10) DEFAULT NULL,
            CHANGE
              provider provider VARCHAR(255) DEFAULT NULL,
            CHANGE
              image_url image_url LONGTEXT DEFAULT NULL,
            CHANGE
              level level VARCHAR(32) DEFAULT NULL,
            CHANGE
              is_free is_free TINYINT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT NULL,
            CHANGE
              lesson_count lesson_count INT DEFAULT NULL,
            CHANGE
              director_signature director_signature LONGTEXT DEFAULT NULL,
            CHANGE
              updated_date updated_date DATETIME DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE group_members DROP FOREIGN KEY `group_members_ibfk_1`');
        $this->addSql('ALTER TABLE group_members DROP FOREIGN KEY `group_members_ibfk_2`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              group_members
            CHANGE
              role role VARCHAR(20) NOT NULL,
            CHANGE
              joined_date joined_date DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_group_members_group ON group_members');
        $this->addSql('CREATE INDEX IDX_C3A086F3FE54D947 ON group_members (group_id)');
        $this->addSql('DROP INDEX idx_group_members_user ON group_members');
        $this->addSql('CREATE INDEX IDX_C3A086F3A76ED395 ON group_members (user_id)');
        $this->addSql('DROP INDEX uq_group_member ON group_members');
        $this->addSql('CREATE UNIQUE INDEX unique_group_user ON group_members (group_id, user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              group_members
            ADD
              CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (group_id) REFERENCES community_groups (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              group_members
            ADD
              CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_hire_offers_status ON hire_offers');
        $this->addSql('ALTER TABLE hire_offers DROP FOREIGN KEY `hire_offers_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              hire_offers
            CHANGE
              currency currency VARCHAR(10) DEFAULT NULL,
            CHANGE
              contract_type contract_type VARCHAR(20) DEFAULT NULL,
            CHANGE
              benefits benefits LONGTEXT DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_hire_offers_application ON hire_offers');
        $this->addSql('CREATE INDEX IDX_5425907D3E030ACD ON hire_offers (application_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              hire_offers
            ADD
              CONSTRAINT `hire_offers_ibfk_1` FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interviews
            DROP
              INDEX idx_interviews_application,
            ADD
              UNIQUE INDEX UNIQ_3A7526823E030ACD (application_id)
        SQL);
        $this->addSql('DROP INDEX idx_interviews_date ON interviews');
        $this->addSql('DROP INDEX idx_interviews_status ON interviews');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interviews
            DROP
              interview_format,
            DROP
              interview_date,
            DROP
              interview_status,
            CHANGE
              scheduled_date scheduled_date DATETIME DEFAULT NULL,
            CHANGE
              duration_minutes duration_minutes INT DEFAULT NULL,
            CHANGE
              type type VARCHAR(20) DEFAULT 'ONLINE' NOT NULL,
            CHANGE
              location location VARCHAR(150) DEFAULT NULL,
            CHANGE
              video_link video_link VARCHAR(500) DEFAULT NULL,
            CHANGE
              notes notes LONGTEXT DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT 'SCHEDULED' NOT NULL,
            CHANGE
              feedback feedback LONGTEXT DEFAULT NULL,
            CHANGE
              rating rating INT DEFAULT NULL,
            CHANGE
              timezone timezone VARCHAR(50) DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME NOT NULL
        SQL);
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY `job_offers_ibfk_1`');
        $this->addSql('DROP INDEX idx_job_offers_status ON job_offers');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY `job_offers_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_offers
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              requirements requirements LONGTEXT DEFAULT NULL,
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'EUR' NOT NULL,
            CHANGE
              posted_date posted_date DATETIME NOT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT 'OPEN' NOT NULL,
            CHANGE
              updated_at updated_at DATETIME NOT NULL,
            CHANGE
              skills_required skills_required LONGTEXT DEFAULT NULL,
            CHANGE
              benefits benefits LONGTEXT DEFAULT NULL,
            CHANGE
              is_featured is_featured TINYINT DEFAULT 0 NOT NULL,
            CHANGE
              views_count views_count INT DEFAULT 0 NOT NULL,
            CHANGE
              applications_count applications_count INT DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_offers
            ADD
              CONSTRAINT FK_8A4229A6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql('DROP INDEX idx_job_offers_company_id ON job_offers');
        $this->addSql('CREATE INDEX IDX_8A4229A6979B1AD6 ON job_offers (company_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_offers
            ADD
              CONSTRAINT `job_offers_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE job_preferences DROP FOREIGN KEY `job_preferences_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_preferences
            CHANGE
              preferred_currency preferred_currency VARCHAR(10) DEFAULT NULL,
            CHANGE
              is_remote_ok is_remote_ok TINYINT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT NULL,
            CHANGE
              updated_date updated_date DATETIME DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX uq_job_pref_user ON job_preferences');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8A5DBDBCA76ED395 ON job_preferences (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_preferences
            ADD
              CONSTRAINT `job_preferences_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY `lesson_progress_ibfk_1`');
        $this->addSql('DROP INDEX uq_lesson_progress ON lesson_progress');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY `lesson_progress_ibfk_1`');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY `lesson_progress_ibfk_2`');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY `lesson_progress_ibfk_3`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            CHANGE
              completed completed TINYINT DEFAULT NULL,
            CHANGE
              progress_percentage progress_percentage NUMERIC(5, 2) DEFAULT NULL,
            CHANGE
              time_spent_minutes time_spent_minutes INT DEFAULT NULL,
            CHANGE
              last_accessed last_accessed DATETIME DEFAULT NULL,
            CHANGE
              notes notes LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT FK_6A46B85F8F7DB25B FOREIGN KEY (enrollment_id) REFERENCES formation_enrollments (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_lp_enrollment ON lesson_progress');
        $this->addSql('CREATE INDEX IDX_6A46B85F8F7DB25B ON lesson_progress (enrollment_id)');
        $this->addSql('DROP INDEX module_id ON lesson_progress');
        $this->addSql('CREATE INDEX IDX_6A46B85FAFC2B591 ON lesson_progress (module_id)');
        $this->addSql('DROP INDEX idx_lp_user ON lesson_progress');
        $this->addSql('CREATE INDEX IDX_6A46B85FA76ED395 ON lesson_progress (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT `lesson_progress_ibfk_3` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE login_history DROP FOREIGN KEY `FK_37976E36A76ED395`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              login_history
            CHANGE
              user_id user_id INT NOT NULL,
            CHANGE
              ip ip VARCHAR(45) DEFAULT NULL,
            CHANGE
              user_agent user_agent LONGTEXT DEFAULT NULL,
            CHANGE
              method method VARCHAR(50) DEFAULT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_37976e36a76ed395 ON login_history');
        $this->addSql('CREATE INDEX idx_login_history_user ON login_history (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              login_history
            ADD
              CONSTRAINT `FK_37976E36A76ED395` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE member_invitations DROP FOREIGN KEY `fk_inv_invitee`');
        $this->addSql('ALTER TABLE member_invitations DROP FOREIGN KEY `fk_inv_inviter`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              member_invitations
            CHANGE
              status status VARCHAR(20) NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX fk_inv_inviter ON member_invitations');
        $this->addSql('CREATE INDEX IDX_FA1A046CB79F4F04 ON member_invitations (inviter_id)');
        $this->addSql('DROP INDEX fk_inv_invitee ON member_invitations');
        $this->addSql('CREATE INDEX IDX_FA1A046C7A512022 ON member_invitations (invitee_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              member_invitations
            ADD
              CONSTRAINT `fk_inv_invitee` FOREIGN KEY (invitee_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              member_invitations
            ADD
              CONSTRAINT `fk_inv_inviter` FOREIGN KEY (inviter_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE passkey_credentials DROP FOREIGN KEY `FK_PASSKEY_USER`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              passkey_credentials
            CHANGE
              credential_id credential_id LONGTEXT NOT NULL,
            CHANGE
              public_key public_key LONGTEXT NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL,
            CHANGE
              last_used_at last_used_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_passkey_user ON passkey_credentials');
        $this->addSql('CREATE INDEX IDX_1D67AC5EA76ED395 ON passkey_credentials (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              passkey_credentials
            ADD
              CONSTRAINT `FK_PASSKEY_USER` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE payment_transactions DROP FOREIGN KEY `payment_transactions_ibfk_1`');
        $this->addSql('ALTER TABLE payment_transactions DROP FOREIGN KEY `payment_transactions_ibfk_2`');
        $this->addSql('ALTER TABLE payment_transactions DROP FOREIGN KEY `payment_transactions_ibfk_3`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            CHANGE
              currency currency VARCHAR(10) DEFAULT NULL,
            CHANGE
              transaction_type transaction_type VARCHAR(30) DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT NULL,
            CHANGE
              transaction_date transaction_date DATETIME DEFAULT NULL,
            CHANGE
              notes notes LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_transactions_payslip ON payment_transactions');
        $this->addSql('CREATE INDEX IDX_8C58AD56296F5EA7 ON payment_transactions (payslip_id)');
        $this->addSql('DROP INDEX from_account_id ON payment_transactions');
        $this->addSql('CREATE INDEX IDX_8C58AD56B0CF99BD ON payment_transactions (from_account_id)');
        $this->addSql('DROP INDEX to_account_id ON payment_transactions');
        $this->addSql('CREATE INDEX IDX_8C58AD56BC58BDC7 ON payment_transactions (to_account_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            ADD
              CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (payslip_id) REFERENCES payslips (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            ADD
              CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (from_account_id) REFERENCES bank_accounts (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            ADD
              CONSTRAINT `payment_transactions_ibfk_3` FOREIGN KEY (to_account_id) REFERENCES bank_accounts (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('ALTER TABLE payslips DROP FOREIGN KEY `payslips_ibfk_1`');
        $this->addSql('DROP INDEX uq_payslip_contract_period ON payslips');
        $this->addSql('DROP INDEX uq_payslip_period ON payslips');
        $this->addSql('DROP INDEX idx_payslips_period ON payslips');
        $this->addSql('DROP INDEX idx_payslips_contract ON payslips');
        $this->addSql('ALTER TABLE payslips DROP FOREIGN KEY `payslips_ibfk_2`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payslips
            DROP
              contract_id,
            DROP
              period_month,
            DROP
              period_year,
            DROP
              gross_salary,
            DROP
              net_salary,
            DROP
              cnss_employee,
            DROP
              cnss_employer,
            DROP
              irpp,
            DROP
              payment_status,
            DROP
              payment_date,
            DROP
              pdf_url,
            DROP
              created_date,
            CHANGE
              other_deductions other_deductions DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              bonuses bonuses DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              currency currency VARCHAR(10) DEFAULT NULL,
            CHANGE
              base_salary base_salary DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              overtime_hours overtime_hours DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              overtime_total overtime_total DOUBLE PRECISION DEFAULT NULL,
            CHANGE
              status status VARCHAR(50) DEFAULT NULL,
            CHANGE
              deductions_json deductions_json LONGTEXT DEFAULT NULL,
            CHANGE
              bonuses_json bonuses_json LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_payslips_user ON payslips');
        $this->addSql('CREATE INDEX IDX_A6292EDAA76ED395 ON payslips (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payslips
            ADD
              CONSTRAINT `payslips_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE portfolio_items DROP FOREIGN KEY `portfolio_items_ibfk_1`');
        $this->addSql('ALTER TABLE portfolio_items DROP FOREIGN KEY `portfolio_items_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              portfolio_items
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              project_url project_url LONGTEXT DEFAULT NULL,
            CHANGE
              image_url image_url LONGTEXT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              portfolio_items
            ADD
              CONSTRAINT FK_BC46C308A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql('DROP INDEX idx_portfolio_user ON portfolio_items');
        $this->addSql('CREATE INDEX IDX_BC46C308A76ED395 ON portfolio_items (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              portfolio_items
            ADD
              CONSTRAINT `portfolio_items_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY `FK_post_comments_post`');
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY `post_comments_ibfk_2`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_comments
            CHANGE
              content content LONGTEXT NOT NULL,
            CHANGE
              created_date created_date DATETIME NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_post_comments_post ON post_comments');
        $this->addSql('CREATE INDEX IDX_E0731F8B4B89032C ON post_comments (post_id)');
        $this->addSql('DROP INDEX author_id ON post_comments');
        $this->addSql('CREATE INDEX IDX_E0731F8BF675F31B ON post_comments (author_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_comments
            ADD
              CONSTRAINT `FK_post_comments_post` FOREIGN KEY (post_id) REFERENCES community_posts (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_comments
            ADD
              CONSTRAINT `post_comments_ibfk_2` FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY `FK_post_likes_post`');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY `post_likes_ibfk_2`');
        $this->addSql('ALTER TABLE post_likes CHANGE created_date created_date DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_post_likes_post ON post_likes');
        $this->addSql('CREATE INDEX IDX_DED1C2924B89032C ON post_likes (post_id)');
        $this->addSql('DROP INDEX user_id ON post_likes');
        $this->addSql('CREATE INDEX IDX_DED1C292A76ED395 ON post_likes (user_id)');
        $this->addSql('DROP INDEX uq_post_like ON post_likes');
        $this->addSql('CREATE UNIQUE INDEX unique_user_post_like ON post_likes (post_id, user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_likes
            ADD
              CONSTRAINT `FK_post_likes_post` FOREIGN KEY (post_id) REFERENCES community_posts (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_likes
            ADD
              CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE profiles DROP FOREIGN KEY `profiles_ibfk_1`');
        $this->addSql('ALTER TABLE profiles DROP FOREIGN KEY `profiles_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              profiles
            CHANGE
              cv_url cv_url LONGTEXT DEFAULT NULL,
            CHANGE
              bio bio LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              profiles
            ADD
              CONSTRAINT FK_8B308530A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql('DROP INDEX idx_profiles_user_id ON profiles');
        $this->addSql('CREATE INDEX IDX_8B308530A76ED395 ON profiles (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              profiles
            ADD
              CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE quiz_questions DROP FOREIGN KEY `quiz_questions_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_questions
            CHANGE
              question_text question_text LONGTEXT NOT NULL,
            CHANGE
              correct_option correct_option VARCHAR(1) NOT NULL,
            CHANGE
              points points INT DEFAULT NULL,
            CHANGE
              order_index order_index INT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX quiz_id ON quiz_questions');
        $this->addSql('CREATE INDEX IDX_8CBC2533853CD175 ON quiz_questions (quiz_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_questions
            ADD
              CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE quiz_results DROP FOREIGN KEY `quiz_results_ibfk_1`');
        $this->addSql('ALTER TABLE quiz_results DROP FOREIGN KEY `quiz_results_ibfk_2`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_results
            CHANGE
              score score INT DEFAULT NULL,
            CHANGE
              max_score max_score INT DEFAULT NULL,
            CHANGE
              passed passed TINYINT DEFAULT NULL,
            CHANGE
              attempt_number attempt_number INT DEFAULT NULL,
            CHANGE
              taken_date taken_date DATETIME DEFAULT NULL,
            CHANGE
              time_spent_seconds time_spent_seconds INT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_quiz_results_quiz ON quiz_results');
        $this->addSql('CREATE INDEX IDX_8DF949B4853CD175 ON quiz_results (quiz_id)');
        $this->addSql('DROP INDEX idx_quiz_results_user ON quiz_results');
        $this->addSql('CREATE INDEX IDX_8DF949B4A76ED395 ON quiz_results (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_results
            ADD
              CONSTRAINT `quiz_results_ibfk_1` FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_results
            ADD
              CONSTRAINT `quiz_results_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE quizzes DROP FOREIGN KEY `quizzes_ibfk_2`');
        $this->addSql('ALTER TABLE quizzes DROP FOREIGN KEY `quizzes_ibfk_1`');
        $this->addSql('ALTER TABLE quizzes DROP FOREIGN KEY `quizzes_ibfk_2`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            CHANGE
              description description LONGTEXT DEFAULT NULL,
            CHANGE
              pass_score pass_score INT DEFAULT NULL,
            CHANGE
              max_attempts max_attempts INT DEFAULT NULL,
            CHANGE
              time_limit_minutes time_limit_minutes INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            ADD
              CONSTRAINT FK_94DC9FB5AFC2B591 FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX formation_id ON quizzes');
        $this->addSql('CREATE INDEX IDX_94DC9FB55200282E ON quizzes (formation_id)');
        $this->addSql('DROP INDEX module_id ON quizzes');
        $this->addSql('CREATE INDEX IDX_94DC9FB5AFC2B591 ON quizzes (module_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            ADD
              CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            ADD
              CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('DROP INDEX uq_saved_jobs_user_url ON saved_jobs');
        $this->addSql('DROP INDEX idx_saved_jobs_user_time ON saved_jobs');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saved_jobs
            CHANGE
              job_url job_url LONGTEXT NOT NULL,
            CHANGE
              saved_at saved_at DATETIME NOT NULL
        SQL);
        $this->addSql('ALTER TABLE skills DROP FOREIGN KEY `skills_ibfk_1`');
        $this->addSql('ALTER TABLE skills DROP FOREIGN KEY `skills_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              skills
            ADD
              CONSTRAINT FK_D5311670CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql('DROP INDEX idx_skills_profile_id ON skills');
        $this->addSql('CREATE INDEX IDX_D5311670CCFA12B8 ON skills (profile_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              skills
            ADD
              CONSTRAINT `skills_ibfk_1` FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE support_tickets DROP FOREIGN KEY `support_tickets_ibfk_1`');
        $this->addSql('ALTER TABLE support_tickets DROP FOREIGN KEY `support_tickets_ibfk_2`');
        $this->addSql('DROP INDEX idx_tickets_assigned ON support_tickets');
        $this->addSql('DROP INDEX idx_tickets_user ON support_tickets');
        $this->addSql('DROP INDEX idx_tickets_status ON support_tickets');
        $this->addSql('DROP INDEX idx_tickets_priority ON support_tickets');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              support_tickets
            CHANGE
              priority priority VARCHAR(20) NOT NULL,
            CHANGE
              status status VARCHAR(20) NOT NULL,
            CHANGE
              description description LONGTEXT NOT NULL,
            CHANGE
              created_date created_date DATETIME NOT NULL,
            CHANGE
              updated_date updated_date DATETIME DEFAULT NULL
        SQL);
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY `ticket_attachments_ibfk_2`');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY `ticket_attachments_ibfk_1`');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY `ticket_attachments_ibfk_2`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            CHANGE
              file_path file_path LONGTEXT NOT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            ADD
              CONSTRAINT FK_2B54FCA9537A1329 FOREIGN KEY (message_id) REFERENCES ticket_messages (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('DROP INDEX idx_ticket_attachments_ticket ON ticket_attachments');
        $this->addSql('CREATE INDEX IDX_2B54FCA9700047D2 ON ticket_attachments (ticket_id)');
        $this->addSql('DROP INDEX idx_ticket_attachments_message ON ticket_attachments');
        $this->addSql('CREATE INDEX IDX_2B54FCA9537A1329 ON ticket_attachments (message_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            ADD
              CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            ADD
              CONSTRAINT `ticket_attachments_ibfk_2` FOREIGN KEY (message_id) REFERENCES ticket_messages (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE ticket_messages DROP FOREIGN KEY `ticket_messages_ibfk_2`');
        $this->addSql('DROP INDEX sender_id ON ticket_messages');
        $this->addSql('ALTER TABLE ticket_messages DROP FOREIGN KEY `ticket_messages_ibfk_1`');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_messages
            CHANGE
              message message LONGTEXT NOT NULL,
            CHANGE
              is_internal is_internal TINYINT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME NOT NULL,
            CHANGE
              attachments_json attachments_json LONGTEXT DEFAULT NULL,
            CHANGE
              audio_path audio_path LONGTEXT DEFAULT NULL,
            CHANGE
              is_audio is_audio TINYINT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_ticket_messages_ticket ON ticket_messages');
        $this->addSql('CREATE INDEX IDX_5E6BE217700047D2 ON ticket_messages (ticket_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_messages
            ADD
              CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_users_email ON users');
        $this->addSql('DROP INDEX username ON users');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              users
            CHANGE
              email email VARCHAR(255) DEFAULT NULL,
            CHANGE
              password password VARCHAR(255) DEFAULT NULL,
            CHANGE
              role role VARCHAR(50) DEFAULT NULL,
            CHANGE
              full_name full_name VARCHAR(255) DEFAULT NULL,
            CHANGE
              created_at created_at DATETIME DEFAULT NULL,
            CHANGE
              is_verified is_verified TINYINT DEFAULT 0 NOT NULL,
            CHANGE
              is_active is_active TINYINT DEFAULT 1 NOT NULL,
            CHANGE
              two_factor_enabled two_factor_enabled TINYINT DEFAULT 0 NOT NULL,
            CHANGE
              terms_accepted terms_accepted TINYINT DEFAULT 0 NOT NULL
        SQL);
        $this->addSql('ALTER TABLE wallets DROP FOREIGN KEY `FK_WALLET_USER`');
        $this->addSql('DROP INDEX uniq_wallet_user ON wallets');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_967AAA6CA76ED395 ON wallets (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              wallets
            ADD
              CONSTRAINT `FK_WALLET_USER` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F091BD8781');
        $this->addSql('DROP INDEX IDX_F7C966F091BD8781 ON applications');
        $this->addSql('DROP INDEX uniq_application_candidate ON applications');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F03481D195');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            CHANGE
              cv_path cv_path VARCHAR(500) DEFAULT '',
            CHANGE
              cover_letter cover_letter TEXT DEFAULT NULL,
            CHANGE
              custom_cv_url custom_cv_url TEXT DEFAULT NULL,
            CHANGE
              match_percentage match_percentage NUMERIC(5, 2) DEFAULT '0.00',
            CHANGE
              candidate_score candidate_score INT DEFAULT 0,
            CHANGE
              applied_date applied_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              applied_at applied_at DATETIME DEFAULT NULL,
            CHANGE
              candidate_id candidate_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            ADD
              CONSTRAINT `applications_ibfk_2` FOREIGN KEY (candidate_profile_id) REFERENCES profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX idx_applications_candidate_id ON applications (candidate_profile_id)');
        $this->addSql('CREATE INDEX idx_applications_status ON applications (status)');
        $this->addSql('DROP INDEX idx_f7c966f03481d195 ON applications');
        $this->addSql('CREATE INDEX idx_applications_job_offer_id ON applications (job_offer_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              applications
            ADD
              CONSTRAINT FK_F7C966F03481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offers (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE bank_accounts DROP FOREIGN KEY FK_FB88842BA76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bank_accounts
            ADD
              account_holder VARCHAR(255) DEFAULT NULL,
            ADD
              swift_bic VARCHAR(11) DEFAULT NULL,
            ADD
              rib VARCHAR(24) DEFAULT NULL,
            ADD
              created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              iban iban VARCHAR(34) DEFAULT NULL,
            CHANGE
              swift swift VARCHAR(11) DEFAULT NULL,
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'TND',
            CHANGE
              is_primary is_primary TINYINT DEFAULT 0,
            CHANGE
              is_verified is_verified TINYINT DEFAULT 0
        SQL);
        $this->addSql('DROP INDEX idx_bank_accounts_user_id ON bank_accounts');
        $this->addSql('CREATE INDEX idx_bank_accounts_user ON bank_accounts (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bank_accounts
            ADD
              CONSTRAINT FK_FB88842BA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE blog_articles DROP FOREIGN KEY FK_CB80154FF675F31B');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              blog_articles
            CHANGE
              title title VARCHAR(300) NOT NULL,
            CHANGE
              content content TEXT NOT NULL,
            CHANGE
              summary summary TEXT DEFAULT NULL,
            CHANGE
              cover_image_url cover_image_url TEXT DEFAULT NULL,
            CHANGE
              category category VARCHAR(50) DEFAULT NULL,
            CHANGE
              views_count views_count INT DEFAULT 0,
            CHANGE
              likes_count likes_count INT DEFAULT 0,
            CHANGE
              is_published is_published TINYINT DEFAULT 0,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              updated_date updated_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('CREATE INDEX idx_blog_published ON blog_articles (is_published, published_date)');
        $this->addSql('DROP INDEX idx_cb80154ff675f31b ON blog_articles');
        $this->addSql('CREATE INDEX idx_blog_author ON blog_articles (author_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              blog_articles
            ADD
              CONSTRAINT FK_CB80154FF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE bonuses DROP FOREIGN KEY FK_8535CFD2A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bonuses
            ADD
              created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              amount amount NUMERIC(12, 2) DEFAULT '0.00' NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_8535cfd2a76ed395 ON bonuses');
        $this->addSql('CREATE INDEX idx_bonuses_user ON bonuses (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              bonuses
            ADD
              CONSTRAINT FK_8535CFD2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE certificates DROP FOREIGN KEY FK_8D26FB5FA76ED395');
        $this->addSql('ALTER TABLE certificates DROP FOREIGN KEY FK_8D26FB5F5200282E');
        $this->addSql('DROP INDEX IDX_8D26FB5FA76ED395 ON certificates');
        $this->addSql('DROP INDEX IDX_8D26FB5F5200282E ON certificates');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              certificates
            ADD
              enrollment_id INT DEFAULT NULL,
            ADD
              certificate_number VARCHAR(50) DEFAULT NULL,
            ADD
              qr_code TEXT DEFAULT NULL,
            ADD
              hash_value VARCHAR(128) DEFAULT NULL,
            ADD
              pdf_url TEXT DEFAULT NULL,
            ADD
              verification_token VARCHAR(128) DEFAULT NULL,
            ADD
              completed_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              certificates
            ADD
              CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE UNIQUE INDEX enrollment_id ON certificates (enrollment_id)');
        $this->addSql('CREATE UNIQUE INDEX certificate_number ON certificates (certificate_number)');
        $this->addSql('DROP INDEX uniq_8d26fb5f1623cb0a ON certificates');
        $this->addSql('CREATE UNIQUE INDEX uniq_certificate_verification_id ON certificates (verification_id)');
        $this->addSql('ALTER TABLE community_events DROP FOREIGN KEY FK_224DA2B9876C4DDA');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_events
            CHANGE
              event_type event_type VARCHAR(30) DEFAULT 'MEETUP' NOT NULL,
            CHANGE
              start_date start_date DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              end_date end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              status status VARCHAR(20) DEFAULT 'UPCOMING' NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('DROP INDEX idx_224da2b9876c4dda ON community_events');
        $this->addSql('CREATE INDEX IDX_community_event_organizer ON community_events (organizer_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_events
            ADD
              CONSTRAINT FK_224DA2B9876C4DDA FOREIGN KEY (organizer_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE community_groups DROP FOREIGN KEY FK_81A7CC8361220EA6');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_groups
            CHANGE
              name name VARCHAR(150) NOT NULL,
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              category category VARCHAR(50) DEFAULT NULL,
            CHANGE
              cover_image_url cover_image_url TEXT DEFAULT NULL,
            CHANGE
              member_count member_count INT DEFAULT 1,
            CHANGE
              is_public is_public TINYINT DEFAULT 1,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('DROP INDEX idx_81a7cc8361220ea6 ON community_groups');
        $this->addSql('CREATE INDEX creator_id ON community_groups (creator_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_groups
            ADD
              CONSTRAINT FK_81A7CC8361220EA6 FOREIGN KEY (creator_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE community_notifications DROP FOREIGN KEY FK_EF2FBEA76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_notifications
            CHANGE
              type type VARCHAR(50) DEFAULT 'INFO' NOT NULL,
            CHANGE
              icon icon VARCHAR(10) DEFAULT '?',
            CHANGE
              created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('DROP INDEX idx_ef2fbea76ed395 ON community_notifications');
        $this->addSql('CREATE INDEX IDX_community_notification_user ON community_notifications (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_notifications
            ADD
              CONSTRAINT FK_EF2FBEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE community_posts DROP FOREIGN KEY FK_F32DC0BEF675F31B');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_posts
            CHANGE
              content content TEXT NOT NULL,
            CHANGE
              created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            CHANGE
              updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_f32dc0bef675f31b ON community_posts');
        $this->addSql('CREATE INDEX fk_community_posts_author ON community_posts (author_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              community_posts
            ADD
              CONSTRAINT FK_F32DC0BEF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE companies DROP FOREIGN KEY FK_8244AA3A7E3C61F9');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              companies
            CHANGE
              logo_url logo_url TEXT DEFAULT NULL,
            CHANGE
              is_verified is_verified TINYINT DEFAULT 0
        SQL);
        $this->addSql('DROP INDEX idx_8244aa3a7e3c61f9 ON companies');
        $this->addSql('CREATE INDEX idx_companies_owner ON companies (owner_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              companies
            ADD
              CONSTRAINT FK_8244AA3A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('ALTER TABLE contracts DROP FOREIGN KEY FK_950A973BD7CCBD6');
        $this->addSql('DROP INDEX IDX_950A973BD7CCBD6 ON contracts');
        $this->addSql('ALTER TABLE contracts DROP FOREIGN KEY FK_950A973A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contracts
            ADD
              created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              type type VARCHAR(30) DEFAULT 'CDI',
            CHANGE
              position position VARCHAR(150) DEFAULT NULL,
            CHANGE
              salary salary NUMERIC(12, 2) DEFAULT '0.00',
            CHANGE
              status status VARCHAR(20) DEFAULT 'ACTIVE'
        SQL);
        $this->addSql('CREATE INDEX idx_contracts_status ON contracts (status)');
        $this->addSql('DROP INDEX idx_950a973a76ed395 ON contracts');
        $this->addSql('CREATE INDEX idx_contracts_user ON contracts (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              contracts
            ADD
              CONSTRAINT FK_950A973A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE dm_conversations DROP FOREIGN KEY FK_3429FA2DC4324F5');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_conversations
            CHANGE
              created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_3429fa2dc4324f5 ON dm_conversations');
        $this->addSql('CREATE INDEX fk_dm_conv_high ON dm_conversations (participant_high_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_conversations
            ADD
              CONSTRAINT FK_3429FA2DC4324F5 FOREIGN KEY (participant_high_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE dm_messages DROP FOREIGN KEY FK_BC5894CE9AC0396');
        $this->addSql('ALTER TABLE dm_messages DROP FOREIGN KEY FK_BC5894CEF624B39D');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_messages
            CHANGE
              body body TEXT NOT NULL,
            CHANGE
              created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            CHANGE
              updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_bc5894ce9ac0396 ON dm_messages');
        $this->addSql('CREATE INDEX fk_dm_msg_conv ON dm_messages (conversation_id)');
        $this->addSql('DROP INDEX idx_bc5894cef624b39d ON dm_messages');
        $this->addSql('CREATE INDEX fk_dm_msg_sender ON dm_messages (sender_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_messages
            ADD
              CONSTRAINT FK_BC5894CE9AC0396 FOREIGN KEY (conversation_id) REFERENCES dm_conversations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              dm_messages
            ADD
              CONSTRAINT FK_BC5894CEF624B39D FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE escrow_accounts DROP FOREIGN KEY FK_9E94F4972576E0FD');
        $this->addSql('ALTER TABLE escrow_accounts DROP FOREIGN KEY FK_9E94F497642B8210');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              escrow_accounts
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'TND',
            CHANGE
              status status VARCHAR(20) DEFAULT 'HOLDING',
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              release_notes release_notes TEXT DEFAULT NULL
        SQL);
        $this->addSql('CREATE INDEX idx_escrow_status ON escrow_accounts (status)');
        $this->addSql('DROP INDEX idx_9e94f497642b8210 ON escrow_accounts');
        $this->addSql('CREATE INDEX idx_escrow_admin ON escrow_accounts (admin_id)');
        $this->addSql('DROP INDEX idx_9e94f4972576e0fd ON escrow_accounts');
        $this->addSql('CREATE INDEX idx_escrow_contract ON escrow_accounts (contract_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              escrow_accounts
            ADD
              CONSTRAINT FK_9E94F4972576E0FD FOREIGN KEY (contract_id) REFERENCES contracts (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              escrow_accounts
            ADD
              CONSTRAINT FK_9E94F497642B8210 FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE event_rsvps DROP FOREIGN KEY FK_CBEEB77E71F7E88B');
        $this->addSql('ALTER TABLE event_rsvps DROP FOREIGN KEY FK_CBEEB77EA76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_rsvps
            CHANGE
              status status VARCHAR(20) DEFAULT 'GOING',
            CHANGE
              rsvp_date rsvp_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('DROP INDEX idx_cbeeb77ea76ed395 ON event_rsvps');
        $this->addSql('CREATE INDEX user_id ON event_rsvps (user_id)');
        $this->addSql('DROP INDEX idx_cbeeb77e71f7e88b ON event_rsvps');
        $this->addSql('CREATE INDEX idx_event_rsvps_event ON event_rsvps (event_id)');
        $this->addSql('DROP INDEX unique_event_user_rsvp ON event_rsvps');
        $this->addSql('CREATE UNIQUE INDEX uq_rsvp ON event_rsvps (event_id, user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_rsvps
            ADD
              CONSTRAINT FK_CBEEB77E71F7E88B FOREIGN KEY (event_id) REFERENCES community_events (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              event_rsvps
            ADD
              CONSTRAINT FK_CBEEB77EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              exchange_rates
            CHANGE
              source source VARCHAR(50) DEFAULT 'BCT',
            CHANGE
              last_updated last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uq_rate ON exchange_rates (from_currency, to_currency, rate_date)');
        $this->addSql('ALTER TABLE experiences DROP FOREIGN KEY FK_82020E70CCFA12B8');
        $this->addSql('ALTER TABLE experiences DROP FOREIGN KEY FK_82020E70CCFA12B8');
        $this->addSql('ALTER TABLE experiences CHANGE description description TEXT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              experiences
            ADD
              CONSTRAINT `experiences_ibfk_1` FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_82020e70ccfa12b8 ON experiences');
        $this->addSql('CREATE INDEX idx_experiences_profile_id ON experiences (profile_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              experiences
            ADD
              CONSTRAINT FK_82020E70CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formations
            CHANGE
              title title VARCHAR(200) NOT NULL,
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              cost cost NUMERIC(10, 2) DEFAULT '0.00',
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'TND',
            CHANGE
              duration_hours duration_hours INT DEFAULT 0,
            CHANGE
              lesson_count lesson_count INT DEFAULT 0,
            CHANGE
              level level VARCHAR(30) DEFAULT 'BEGINNER',
            CHANGE
              category category VARCHAR(50) NOT NULL,
            CHANGE
              provider provider VARCHAR(100) DEFAULT NULL,
            CHANGE
              image_url image_url TEXT DEFAULT NULL,
            CHANGE
              is_free is_free TINYINT DEFAULT 1,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              updated_date updated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              status status VARCHAR(20) DEFAULT 'ACTIVE',
            CHANGE
              director_signature director_signature TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formations
            ADD
              CONSTRAINT `formations_ibfk_1` FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('CREATE INDEX idx_formations_status ON formations (status)');
        $this->addSql('CREATE INDEX created_by ON formations (created_by)');
        $this->addSql('CREATE INDEX idx_formations_category ON formations (category)');
        $this->addSql('ALTER TABLE formation_enrollments DROP FOREIGN KEY FK_9325E1E95200282E');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_enrollments
            CHANGE
              enrolled_at enrolled_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              completed_at completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('DROP INDEX idx_9325e1e95200282e ON formation_enrollments');
        $this->addSql('CREATE INDEX fk_enrollment_formation ON formation_enrollments (formation_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_enrollments
            ADD
              CONSTRAINT FK_9325E1E95200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formation_materials DROP FOREIGN KEY FK_516A46685200282E');
        $this->addSql('ALTER TABLE formation_materials DROP FOREIGN KEY FK_516A4668E3E73126');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_materials
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              file_url file_url TEXT DEFAULT NULL,
            CHANGE
              file_size file_size BIGINT DEFAULT 0,
            CHANGE
              download_count download_count INT DEFAULT 0,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('DROP INDEX idx_516a46685200282e ON formation_materials');
        $this->addSql('CREATE INDEX idx_fmat_formation ON formation_materials (formation_id)');
        $this->addSql('DROP INDEX idx_516a4668e3e73126 ON formation_materials');
        $this->addSql('CREATE INDEX uploaded_by ON formation_materials (uploaded_by)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_materials
            ADD
              CONSTRAINT FK_516A46685200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_materials
            ADD
              CONSTRAINT FK_516A4668E3E73126 FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('ALTER TABLE formation_modules DROP FOREIGN KEY FK_6B4806AC5200282E');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_modules
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              content_url content_url TEXT DEFAULT NULL,
            CHANGE
              duration_minutes duration_minutes INT DEFAULT 0,
            CHANGE
              order_index order_index INT DEFAULT 0,
            CHANGE
              content content TEXT DEFAULT NULL,
            CHANGE
              created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('DROP INDEX idx_6b4806ac5200282e ON formation_modules');
        $this->addSql('CREATE INDEX formation_id ON formation_modules (formation_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_modules
            ADD
              CONSTRAINT FK_6B4806AC5200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY FK_2C8FAE745200282E');
        $this->addSql('ALTER TABLE formation_reviews DROP FOREIGN KEY FK_2C8FAE74A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_reviews
            CHANGE
              created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('DROP INDEX idx_2c8fae745200282e ON formation_reviews');
        $this->addSql('CREATE INDEX IDX_formation_reviews_formation ON formation_reviews (formation_id)');
        $this->addSql('DROP INDEX idx_2c8fae74a76ed395 ON formation_reviews');
        $this->addSql('CREATE INDEX FK_formation_reviews_user ON formation_reviews (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_reviews
            ADD
              CONSTRAINT FK_2C8FAE745200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_reviews
            ADD
              CONSTRAINT FK_2C8FAE74A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE formation_review_likes DROP FOREIGN KEY FK_4B1A941A3E2E969B');
        $this->addSql('ALTER TABLE formation_review_likes DROP FOREIGN KEY FK_4B1A941AA76ED395');
        $this->addSql('DROP INDEX idx_4b1a941aa76ed395 ON formation_review_likes');
        $this->addSql('CREATE INDEX IDX_review_likes_user ON formation_review_likes (user_id)');
        $this->addSql('DROP INDEX idx_4b1a941a3e2e969b ON formation_review_likes');
        $this->addSql('CREATE INDEX IDX_review_likes_review ON formation_review_likes (review_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_review_likes
            ADD
              CONSTRAINT FK_4B1A941A3E2E969B FOREIGN KEY (review_id) REFERENCES formation_reviews (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              formation_review_likes
            ADD
              CONSTRAINT FK_4B1A941AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE group_members DROP FOREIGN KEY FK_C3A086F3FE54D947');
        $this->addSql('ALTER TABLE group_members DROP FOREIGN KEY FK_C3A086F3A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              group_members
            CHANGE
              role role VARCHAR(20) DEFAULT 'MEMBER',
            CHANGE
              joined_date joined_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('DROP INDEX idx_c3a086f3fe54d947 ON group_members');
        $this->addSql('CREATE INDEX idx_group_members_group ON group_members (group_id)');
        $this->addSql('DROP INDEX idx_c3a086f3a76ed395 ON group_members');
        $this->addSql('CREATE INDEX idx_group_members_user ON group_members (user_id)');
        $this->addSql('DROP INDEX unique_group_user ON group_members');
        $this->addSql('CREATE UNIQUE INDEX uq_group_member ON group_members (group_id, user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              group_members
            ADD
              CONSTRAINT FK_C3A086F3FE54D947 FOREIGN KEY (group_id) REFERENCES community_groups (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              group_members
            ADD
              CONSTRAINT FK_C3A086F3A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE hire_offers DROP FOREIGN KEY FK_5425907D3E030ACD');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              hire_offers
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'TND',
            CHANGE
              contract_type contract_type VARCHAR(20) DEFAULT 'CDI',
            CHANGE
              benefits benefits TEXT DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT 'PENDING',
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('CREATE INDEX idx_hire_offers_status ON hire_offers (status)');
        $this->addSql('DROP INDEX idx_5425907d3e030acd ON hire_offers');
        $this->addSql('CREATE INDEX idx_hire_offers_application ON hire_offers (application_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              hire_offers
            ADD
              CONSTRAINT FK_5425907D3E030ACD FOREIGN KEY (application_id) REFERENCES applications (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interviews
            DROP
              INDEX UNIQ_3A7526823E030ACD,
            ADD
              INDEX idx_interviews_application (application_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              interviews
            ADD
              interview_format VARCHAR(20) DEFAULT 'ONLINE' NOT NULL,
            ADD
              interview_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
            ADD
              interview_status VARCHAR(20) DEFAULT 'SCHEDULED' NOT NULL,
            CHANGE
              scheduled_date scheduled_date DATETIME NOT NULL,
            CHANGE
              duration_minutes duration_minutes INT DEFAULT 60,
            CHANGE
              type type VARCHAR(20) DEFAULT 'VIDEO',
            CHANGE
              location location VARCHAR(255) DEFAULT NULL,
            CHANGE
              video_link video_link TEXT DEFAULT NULL,
            CHANGE
              notes notes TEXT DEFAULT NULL,
            CHANGE
              status status VARCHAR(20) DEFAULT 'SCHEDULED',
            CHANGE
              feedback feedback TEXT DEFAULT NULL,
            CHANGE
              rating rating INT DEFAULT 0,
            CHANGE
              timezone timezone VARCHAR(50) DEFAULT 'Africa/Tunis',
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('CREATE INDEX idx_interviews_date ON interviews (scheduled_date)');
        $this->addSql('CREATE INDEX idx_interviews_status ON interviews (status)');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY FK_8A4229A6979B1AD6');
        $this->addSql('ALTER TABLE job_offers DROP FOREIGN KEY FK_8A4229A6979B1AD6');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_offers
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              requirements requirements TEXT DEFAULT NULL,
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'EUR',
            CHANGE
              posted_date posted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              status status VARCHAR(20) DEFAULT 'OPEN',
            CHANGE
              updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              skills_required skills_required TEXT DEFAULT NULL,
            CHANGE
              benefits benefits TEXT DEFAULT NULL,
            CHANGE
              is_featured is_featured TINYINT DEFAULT 0,
            CHANGE
              views_count views_count INT DEFAULT 0,
            CHANGE
              applications_count applications_count INT DEFAULT 0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_offers
            ADD
              CONSTRAINT `job_offers_ibfk_1` FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX idx_job_offers_status ON job_offers (status)');
        $this->addSql('DROP INDEX idx_8a4229a6979b1ad6 ON job_offers');
        $this->addSql('CREATE INDEX idx_job_offers_company_id ON job_offers (company_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_offers
            ADD
              CONSTRAINT FK_8A4229A6979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)
        SQL);
        $this->addSql('ALTER TABLE job_preferences DROP FOREIGN KEY FK_8A5DBDBCA76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_preferences
            CHANGE
              preferred_currency preferred_currency VARCHAR(10) DEFAULT 'TND',
            CHANGE
              is_remote_ok is_remote_ok TINYINT DEFAULT 1,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              updated_date updated_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('DROP INDEX uniq_8a5dbdbca76ed395 ON job_preferences');
        $this->addSql('CREATE UNIQUE INDEX uq_job_pref_user ON job_preferences (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              job_preferences
            ADD
              CONSTRAINT FK_8A5DBDBCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85F8F7DB25B');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85F8F7DB25B');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85FAFC2B591');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85FA76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            CHANGE
              completed completed TINYINT DEFAULT 0,
            CHANGE
              progress_percentage progress_percentage NUMERIC(5, 2) DEFAULT '0.00',
            CHANGE
              time_spent_minutes time_spent_minutes INT DEFAULT 0,
            CHANGE
              last_accessed last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              notes notes TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uq_lesson_progress ON lesson_progress (enrollment_id, module_id)');
        $this->addSql('DROP INDEX idx_6a46b85fa76ed395 ON lesson_progress');
        $this->addSql('CREATE INDEX idx_lp_user ON lesson_progress (user_id)');
        $this->addSql('DROP INDEX idx_6a46b85fafc2b591 ON lesson_progress');
        $this->addSql('CREATE INDEX module_id ON lesson_progress (module_id)');
        $this->addSql('DROP INDEX idx_6a46b85f8f7db25b ON lesson_progress');
        $this->addSql('CREATE INDEX idx_lp_enrollment ON lesson_progress (enrollment_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT FK_6A46B85F8F7DB25B FOREIGN KEY (enrollment_id) REFERENCES formation_enrollments (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT FK_6A46B85FAFC2B591 FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              lesson_progress
            ADD
              CONSTRAINT FK_6A46B85FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE login_history DROP FOREIGN KEY FK_37976E36A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              login_history
            CHANGE
              ip ip VARCHAR(45) NOT NULL,
            CHANGE
              user_agent user_agent LONGTEXT NOT NULL,
            CHANGE
              method method VARCHAR(20) NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              user_id user_id INT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_login_history_user ON login_history');
        $this->addSql('CREATE INDEX IDX_37976E36A76ED395 ON login_history (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              login_history
            ADD
              CONSTRAINT FK_37976E36A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE member_invitations DROP FOREIGN KEY FK_FA1A046CB79F4F04');
        $this->addSql('ALTER TABLE member_invitations DROP FOREIGN KEY FK_FA1A046C7A512022');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              member_invitations
            CHANGE
              status status VARCHAR(20) DEFAULT 'pending' NOT NULL,
            CHANGE
              created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql('DROP INDEX idx_fa1a046c7a512022 ON member_invitations');
        $this->addSql('CREATE INDEX fk_inv_invitee ON member_invitations (invitee_id)');
        $this->addSql('DROP INDEX idx_fa1a046cb79f4f04 ON member_invitations');
        $this->addSql('CREATE INDEX fk_inv_inviter ON member_invitations (inviter_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              member_invitations
            ADD
              CONSTRAINT FK_FA1A046CB79F4F04 FOREIGN KEY (inviter_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              member_invitations
            ADD
              CONSTRAINT FK_FA1A046C7A512022 FOREIGN KEY (invitee_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE passkey_credentials DROP FOREIGN KEY FK_1D67AC5EA76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              passkey_credentials
            CHANGE
              credential_id credential_id TEXT NOT NULL,
            CHANGE
              public_key public_key TEXT NOT NULL,
            CHANGE
              created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              last_used_at last_used_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql('DROP INDEX idx_1d67ac5ea76ed395 ON passkey_credentials');
        $this->addSql('CREATE INDEX IDX_PASSKEY_USER ON passkey_credentials (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              passkey_credentials
            ADD
              CONSTRAINT FK_1D67AC5EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE payment_transactions DROP FOREIGN KEY FK_8C58AD56296F5EA7');
        $this->addSql('ALTER TABLE payment_transactions DROP FOREIGN KEY FK_8C58AD56B0CF99BD');
        $this->addSql('ALTER TABLE payment_transactions DROP FOREIGN KEY FK_8C58AD56BC58BDC7');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'TND',
            CHANGE
              transaction_type transaction_type VARCHAR(30) DEFAULT 'SALARY',
            CHANGE
              status status VARCHAR(20) DEFAULT 'PENDING',
            CHANGE
              transaction_date transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              notes notes TEXT DEFAULT NULL
        SQL);
        $this->addSql('DROP INDEX idx_8c58ad56bc58bdc7 ON payment_transactions');
        $this->addSql('CREATE INDEX to_account_id ON payment_transactions (to_account_id)');
        $this->addSql('DROP INDEX idx_8c58ad56296f5ea7 ON payment_transactions');
        $this->addSql('CREATE INDEX idx_transactions_payslip ON payment_transactions (payslip_id)');
        $this->addSql('DROP INDEX idx_8c58ad56b0cf99bd ON payment_transactions');
        $this->addSql('CREATE INDEX from_account_id ON payment_transactions (from_account_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            ADD
              CONSTRAINT FK_8C58AD56296F5EA7 FOREIGN KEY (payslip_id) REFERENCES payslips (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            ADD
              CONSTRAINT FK_8C58AD56B0CF99BD FOREIGN KEY (from_account_id) REFERENCES bank_accounts (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payment_transactions
            ADD
              CONSTRAINT FK_8C58AD56BC58BDC7 FOREIGN KEY (to_account_id) REFERENCES bank_accounts (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('ALTER TABLE payslips DROP FOREIGN KEY FK_A6292EDAA76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payslips
            ADD
              contract_id INT DEFAULT NULL,
            ADD
              period_month INT DEFAULT NULL,
            ADD
              period_year INT DEFAULT NULL,
            ADD
              gross_salary NUMERIC(12, 2) DEFAULT NULL,
            ADD
              net_salary NUMERIC(12, 2) DEFAULT NULL,
            ADD
              cnss_employee NUMERIC(10, 2) DEFAULT '0.00',
            ADD
              cnss_employer NUMERIC(10, 2) DEFAULT '0.00',
            ADD
              irpp NUMERIC(10, 2) DEFAULT '0.00',
            ADD
              payment_status VARCHAR(20) DEFAULT 'PENDING',
            ADD
              payment_date DATETIME DEFAULT NULL,
            ADD
              pdf_url TEXT DEFAULT NULL,
            ADD
              created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              base_salary base_salary NUMERIC(12, 2) DEFAULT '0.00',
            CHANGE
              overtime_hours overtime_hours NUMERIC(8, 2) DEFAULT '0.00',
            CHANGE
              overtime_total overtime_total NUMERIC(12, 2) DEFAULT '0.00',
            CHANGE
              bonuses bonuses NUMERIC(10, 2) DEFAULT '0.00',
            CHANGE
              other_deductions other_deductions NUMERIC(10, 2) DEFAULT '0.00',
            CHANGE
              currency currency VARCHAR(10) DEFAULT 'TND',
            CHANGE
              status status VARCHAR(20) DEFAULT 'PENDING',
            CHANGE
              deductions_json deductions_json TEXT DEFAULT NULL,
            CHANGE
              bonuses_json bonuses_json TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payslips
            ADD
              CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (contract_id) REFERENCES employment_contracts (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_payslip_contract_period ON payslips (
              contract_id, period_month, period_year
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uq_payslip_period ON payslips (contract_id, period_month, period_year)');
        $this->addSql('CREATE INDEX idx_payslips_period ON payslips (period_year, period_month)');
        $this->addSql('CREATE INDEX idx_payslips_contract ON payslips (contract_id)');
        $this->addSql('DROP INDEX idx_a6292edaa76ed395 ON payslips');
        $this->addSql('CREATE INDEX idx_payslips_user ON payslips (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              payslips
            ADD
              CONSTRAINT FK_A6292EDAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE portfolio_items DROP FOREIGN KEY FK_BC46C308A76ED395');
        $this->addSql('ALTER TABLE portfolio_items DROP FOREIGN KEY FK_BC46C308A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              portfolio_items
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              project_url project_url TEXT DEFAULT NULL,
            CHANGE
              image_url image_url TEXT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              portfolio_items
            ADD
              CONSTRAINT `portfolio_items_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_bc46c308a76ed395 ON portfolio_items');
        $this->addSql('CREATE INDEX idx_portfolio_user ON portfolio_items (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              portfolio_items
            ADD
              CONSTRAINT FK_BC46C308A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY FK_E0731F8B4B89032C');
        $this->addSql('ALTER TABLE post_comments DROP FOREIGN KEY FK_E0731F8BF675F31B');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_comments
            CHANGE
              content content TEXT NOT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql('DROP INDEX idx_e0731f8bf675f31b ON post_comments');
        $this->addSql('CREATE INDEX author_id ON post_comments (author_id)');
        $this->addSql('DROP INDEX idx_e0731f8b4b89032c ON post_comments');
        $this->addSql('CREATE INDEX idx_post_comments_post ON post_comments (post_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_comments
            ADD
              CONSTRAINT FK_E0731F8B4B89032C FOREIGN KEY (post_id) REFERENCES community_posts (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_comments
            ADD
              CONSTRAINT FK_E0731F8BF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY FK_DED1C2924B89032C');
        $this->addSql('ALTER TABLE post_likes DROP FOREIGN KEY FK_DED1C292A76ED395');
        $this->addSql('ALTER TABLE post_likes CHANGE created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('DROP INDEX idx_ded1c2924b89032c ON post_likes');
        $this->addSql('CREATE INDEX idx_post_likes_post ON post_likes (post_id)');
        $this->addSql('DROP INDEX unique_user_post_like ON post_likes');
        $this->addSql('CREATE UNIQUE INDEX uq_post_like ON post_likes (post_id, user_id)');
        $this->addSql('DROP INDEX idx_ded1c292a76ed395 ON post_likes');
        $this->addSql('CREATE INDEX user_id ON post_likes (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_likes
            ADD
              CONSTRAINT FK_DED1C2924B89032C FOREIGN KEY (post_id) REFERENCES community_posts (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              post_likes
            ADD
              CONSTRAINT FK_DED1C292A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE profiles DROP FOREIGN KEY FK_8B308530A76ED395');
        $this->addSql('ALTER TABLE profiles DROP FOREIGN KEY FK_8B308530A76ED395');
        $this->addSql('ALTER TABLE profiles CHANGE cv_url cv_url TEXT DEFAULT NULL, CHANGE bio bio TEXT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              profiles
            ADD
              CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_8b308530a76ed395 ON profiles');
        $this->addSql('CREATE INDEX idx_profiles_user_id ON profiles (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              profiles
            ADD
              CONSTRAINT FK_8B308530A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql('ALTER TABLE quizzes DROP FOREIGN KEY FK_94DC9FB5AFC2B591');
        $this->addSql('ALTER TABLE quizzes DROP FOREIGN KEY FK_94DC9FB55200282E');
        $this->addSql('ALTER TABLE quizzes DROP FOREIGN KEY FK_94DC9FB5AFC2B591');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              pass_score pass_score INT DEFAULT 70,
            CHANGE
              max_attempts max_attempts INT DEFAULT 3,
            CHANGE
              time_limit_minutes time_limit_minutes INT DEFAULT 30
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            ADD
              CONSTRAINT `quizzes_ibfk_2` FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('DROP INDEX idx_94dc9fb5afc2b591 ON quizzes');
        $this->addSql('CREATE INDEX module_id ON quizzes (module_id)');
        $this->addSql('DROP INDEX idx_94dc9fb55200282e ON quizzes');
        $this->addSql('CREATE INDEX formation_id ON quizzes (formation_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            ADD
              CONSTRAINT FK_94DC9FB55200282E FOREIGN KEY (formation_id) REFERENCES formations (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quizzes
            ADD
              CONSTRAINT FK_94DC9FB5AFC2B591 FOREIGN KEY (module_id) REFERENCES formation_modules (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE quiz_questions DROP FOREIGN KEY FK_8CBC2533853CD175');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_questions
            CHANGE
              question_text question_text TEXT NOT NULL,
            CHANGE
              correct_option correct_option CHAR(1) NOT NULL,
            CHANGE
              points points INT DEFAULT 1,
            CHANGE
              order_index order_index INT DEFAULT 0
        SQL);
        $this->addSql('DROP INDEX idx_8cbc2533853cd175 ON quiz_questions');
        $this->addSql('CREATE INDEX quiz_id ON quiz_questions (quiz_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_questions
            ADD
              CONSTRAINT FK_8CBC2533853CD175 FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE
        SQL);
        $this->addSql('ALTER TABLE quiz_results DROP FOREIGN KEY FK_8DF949B4853CD175');
        $this->addSql('ALTER TABLE quiz_results DROP FOREIGN KEY FK_8DF949B4A76ED395');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_results
            CHANGE
              score score INT DEFAULT 0,
            CHANGE
              max_score max_score INT DEFAULT 0,
            CHANGE
              passed passed TINYINT DEFAULT 0,
            CHANGE
              attempt_number attempt_number INT DEFAULT 1,
            CHANGE
              taken_date taken_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              time_spent_seconds time_spent_seconds INT DEFAULT 0
        SQL);
        $this->addSql('DROP INDEX idx_8df949b4853cd175 ON quiz_results');
        $this->addSql('CREATE INDEX idx_quiz_results_quiz ON quiz_results (quiz_id)');
        $this->addSql('DROP INDEX idx_8df949b4a76ed395 ON quiz_results');
        $this->addSql('CREATE INDEX idx_quiz_results_user ON quiz_results (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_results
            ADD
              CONSTRAINT FK_8DF949B4853CD175 FOREIGN KEY (quiz_id) REFERENCES quizzes (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              quiz_results
            ADD
              CONSTRAINT FK_8DF949B4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              saved_jobs
            CHANGE
              job_url job_url TEXT NOT NULL,
            CHANGE
              saved_at saved_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        SQL);
        $this->addSql('CREATE UNIQUE INDEX uq_saved_jobs_user_url ON saved_jobs (user_id, job_url(255))');
        $this->addSql('CREATE INDEX idx_saved_jobs_user_time ON saved_jobs (user_id, saved_at)');
        $this->addSql('ALTER TABLE skills DROP FOREIGN KEY FK_D5311670CCFA12B8');
        $this->addSql('ALTER TABLE skills DROP FOREIGN KEY FK_D5311670CCFA12B8');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              skills
            ADD
              CONSTRAINT `skills_ibfk_1` FOREIGN KEY (profile_id) REFERENCES profiles (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_d5311670ccfa12b8 ON skills');
        $this->addSql('CREATE INDEX idx_skills_profile_id ON skills (profile_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              skills
            ADD
              CONSTRAINT FK_D5311670CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profiles (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              support_tickets
            CHANGE
              priority priority VARCHAR(20) DEFAULT 'MEDIUM',
            CHANGE
              status status VARCHAR(20) DEFAULT 'OPEN',
            CHANGE
              description description TEXT DEFAULT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              updated_date updated_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              support_tickets
            ADD
              CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              support_tickets
            ADD
              CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('CREATE INDEX idx_tickets_assigned ON support_tickets (assigned_to)');
        $this->addSql('CREATE INDEX idx_tickets_user ON support_tickets (user_id)');
        $this->addSql('CREATE INDEX idx_tickets_status ON support_tickets (status)');
        $this->addSql('CREATE INDEX idx_tickets_priority ON support_tickets (priority)');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY FK_2B54FCA9537A1329');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY FK_2B54FCA9700047D2');
        $this->addSql('ALTER TABLE ticket_attachments DROP FOREIGN KEY FK_2B54FCA9537A1329');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            CHANGE
              file_path file_path TEXT NOT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            ADD
              CONSTRAINT `ticket_attachments_ibfk_2` FOREIGN KEY (message_id) REFERENCES ticket_messages (id) ON DELETE CASCADE
        SQL);
        $this->addSql('DROP INDEX idx_2b54fca9537a1329 ON ticket_attachments');
        $this->addSql('CREATE INDEX idx_ticket_attachments_message ON ticket_attachments (message_id)');
        $this->addSql('DROP INDEX idx_2b54fca9700047d2 ON ticket_attachments');
        $this->addSql('CREATE INDEX idx_ticket_attachments_ticket ON ticket_attachments (ticket_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            ADD
              CONSTRAINT FK_2B54FCA9700047D2 FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_attachments
            ADD
              CONSTRAINT FK_2B54FCA9537A1329 FOREIGN KEY (message_id) REFERENCES ticket_messages (id) ON DELETE
            SET
              NULL
        SQL);
        $this->addSql('ALTER TABLE ticket_messages DROP FOREIGN KEY FK_5E6BE217700047D2');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_messages
            CHANGE
              message message TEXT NOT NULL,
            CHANGE
              created_date created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            CHANGE
              is_internal is_internal TINYINT DEFAULT 0,
            CHANGE
              attachments_json attachments_json TEXT DEFAULT NULL,
            CHANGE
              audio_path audio_path TEXT DEFAULT NULL,
            CHANGE
              is_audio is_audio TINYINT DEFAULT 0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_messages
            ADD
              CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
        $this->addSql('CREATE INDEX sender_id ON ticket_messages (sender_id)');
        $this->addSql('DROP INDEX idx_5e6be217700047d2 ON ticket_messages');
        $this->addSql('CREATE INDEX idx_ticket_messages_ticket ON ticket_messages (ticket_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              ticket_messages
            ADD
              CONSTRAINT FK_5E6BE217700047D2 FOREIGN KEY (ticket_id) REFERENCES support_tickets (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              users
            CHANGE
              email email VARCHAR(100) DEFAULT NULL,
            CHANGE
              password password VARCHAR(255) NOT NULL,
            CHANGE
              role role VARCHAR(20) NOT NULL,
            CHANGE
              full_name full_name VARCHAR(100) NOT NULL,
            CHANGE
              is_verified is_verified TINYINT DEFAULT 0,
            CHANGE
              is_active is_active TINYINT DEFAULT 1,
            CHANGE
              created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            CHANGE
              two_factor_enabled two_factor_enabled TINYINT DEFAULT 0,
            CHANGE
              terms_accepted terms_accepted TINYINT DEFAULT 0
        SQL);
        $this->addSql('CREATE INDEX idx_users_email ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX username ON users (username)');
        $this->addSql('ALTER TABLE wallets DROP FOREIGN KEY FK_967AAA6CA76ED395');
        $this->addSql('DROP INDEX uniq_967aaa6ca76ed395 ON wallets');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_WALLET_USER ON wallets (user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              wallets
            ADD
              CONSTRAINT FK_967AAA6CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        SQL);
    }
}
