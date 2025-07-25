# UGotHere

With UGotHere, safely analyze suspicious links before clicking, learn the real destination and protect yourself.

## ✨ Features

- **URL Tracing:** Uncovers the final URL behind shortened or redirected links.
- **Security-Focused:** Blocks requests to potentially unsafe destinations like `localhost` and private IP ranges.
- **Detailed Information:** Provides statistics such as the number of redirects and processing time.
- **Database Integration:** Stores traced links and statistics (total links traced, links traced today, most popular domain, etc.) in a database.
- **Multi-language Support:** Supports English, Turkish, and German.
- **Modern UI:** Features a sleek and user-friendly interface built with Tailwind CSS.

## 🚀 Installation

Follow the steps below to run the project on your local machine.

### Prerequisites

- PHP 7.4 or higher
- MySQL or MariaDB
- PHP with the cURL extension enabled

### Steps

1.  **Clone the Project:**
    ```bash
    git clone https://github.com/KRYPEX95/UGotHere.git
    cd UGotHere
    ```

2.  **Set Up the Database:**
    - Create a MySQL database.
    - Run the following SQL query to create the `traces` table:
      ```sql
      CREATE TABLE `traces` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `shortened_url` text NOT NULL,
        `final_url` text NOT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
      ```

3.  **Create the Configuration File:**
    - Create a file named `config.php` in the `includes/` directory.
    - Paste the following content into this file and enter your database credentials:
      ```php
      <?php
      $host = '127.0.0.1';
      $db   = 'database_name';
      $user = 'database_user';
      $pass = 'database_password';
      $charset = 'utf8mb4';

      $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
      $options = [
          PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES   => false,
      ];
      ?>
      ```

4.  **Run the Project:**
    - Serve the project directory with a PHP server.

## 🤝 Contributing

Your contributions help make the project better. Please feel free to open a pull request or create an issue.

1.  Fork the Project.
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`).
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`).
4.  Push to the Branch (`git push origin feature/AmazingFeature`).
5.  Open a Pull Request.

## 📄 License

This project is licensed under the MIT License. See the `LICENSE` file for more information.
