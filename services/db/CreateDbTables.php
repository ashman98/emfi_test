<?php
namespace services\db;

use PDOException;
use services\db\DBconnect;

class CreateDbTables
{
    private $pdo = null;
    public function __construct()
    {
        $db = new DBconnect;
        $this->pdo = $db->conn();
    }


    public function create()
    {
        try {
    // Create leads table
    $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS leads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            external_id INT NOT NULL,
            is_prev BOOLEAN NOT NULL DEFAULT FALSE,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10, 2) NOT NULL,
            responsible_user_id INT NOT NULL,
            group_id INT,
            status_id INT NOT NULL,
            pipeline_id INT NOT NULL,
            loss_reason_id INT,
            created_by INT NOT NULL,
            updated_by INT NOT NULL,
            closed_at INT,
            closest_task_at INT,
            is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
            score INT,
            account_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");

    // Create custom_field_values table
    $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS tags (
            id INT PRIMARY KEY AUTO_INCREMENT,
            external_id INT NOT NULL,
            name VARCHAR(255),
            color VARCHAR(255),
            leads_id INT NOT NULL,
            FOREIGN KEY (leads_id) REFERENCES leads(id),
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )
    ");

//
            $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS companies  (
            id INT PRIMARY KEY,
            name VARCHAR(255),
            responsible_user_id INT,
            external_id INT,
            group_id INT,
            created_by INT,
            updated_by INT,
            created_at DATETIME,
            updated_at DATETIME,
            closest_task_at DATETIME,
            account_id INT
        )
    ");
//
//            // Create contacts table
            $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id INT PRIMARY KEY,
            external_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            group_id INT,
            responsible_user_id INT NOT NULL,
            created_by INT NOT NULL,
            updated_by INT NOT NULL,
            company_name VARCHAR(255),
            closest_task_at DATETIME,
            company_id  INT,
            account_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
            is_unsorted BOOLEAN NOT NULL DEFAULT FALSE,
            FOREIGN KEY (company_id) REFERENCES companies(id)
        )
    ");
//
//            // Create lead_contact_relation table
            $this->pdo->exec("
            CREATE TABLE lead_contact_relation (
            lead_id INT,
            contact_id INT,
            PRIMARY KEY (lead_id, contact_id),
            FOREIGN KEY (lead_id) REFERENCES leads(id),
            FOREIGN KEY (contact_id) REFERENCES contacts(id)
        );
    ");
//
//            // Create lead_contact_relation table
            $this->pdo->exec("
            CREATE TABLE company_contact_relation (
            company_id INT,
            contact_id INT,
            PRIMARY KEY (company_id, contact_id),
            FOREIGN KEY (company_id) REFERENCES companies(id),
            FOREIGN KEY (contact_id) REFERENCES contacts(id)
        );
    ");
//
//
//            // Create custom_fields table
            $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS custom_fields (
            id INT PRIMARY KEY AUTO_INCREMENT,
            external_id INT NOT NULL,
            field_name VARCHAR(255) NOT NULL,
            field_code VARCHAR(50) NOT NULL,
            field_type VARCHAR(50) NOT NULL,
            entity_type ENUM('lead', 'contact', 'company')
        )
    ");
//
//            // Create custom_field_values table
            $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS custom_field_values (
            id INT PRIMARY KEY AUTO_INCREMENT,
            entity_type ENUM('lead', 'contact', 'company') NOT NULL,
            entity_id INT NOT NULL,
            field_id INT,
            value VARCHAR(255),
            enum_id INT,
            enum_code VARCHAR(50),
            FOREIGN KEY (field_id) REFERENCES custom_fields(id)
        )
    ");



            echo "Tables created successfully.";
        } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }
}