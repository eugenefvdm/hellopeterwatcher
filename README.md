# Hello Peter Watcher

Hello Peter Watcher is a PHP application designed to monitor reviews on HelloPeter and send SMS notifications for unreplied reviews using the BulkSMS service.

## Features

- **Fetch Unreplied Reviews**: Connects to the HelloPeter API to retrieve reviews that have not been replied to.
- **Send SMS Notifications**: Uses the BulkSMS API to send notifications about unreplied reviews to specified recipients.
- **Environment Configuration**: Utilizes environment variables for secure configuration management.

## Installation

1. **Clone the Repository**: 
   ```bash
   git clone <repository-url>
   cd <repository-directory>
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment Variables**:
   - Copy `.env.example` to `.env`.
   - Fill in your HelloPeter API key and BulkSMS credentials in the `.env` file.

## Usage

1. **Run the Application**:
   ```bash
   php index.php
   ```

2. **Functionality**:
   - The application will fetch unreplied reviews from HelloPeter.
   - It will output the count and details of these reviews.
   - If there are unreplied reviews, it will send an SMS notification to the recipients specified in the `.env` file.

3. **Set Up a CRON Job**:
   - To automate the process of checking for unreplied reviews and sending notifications, you can set up a CRON job.
   - Open your crontab file by running `crontab -e` in your terminal.
   - Add the following line to schedule the script to run at your desired interval (e.g., every hour):
     ```bash
     0 * * * * /usr/bin/php /path/to/your/application/index.php
     ```
   - Replace `/path/to/your/application/` with the actual path to your application directory.
   - Save and exit the crontab editor.

## Components

- **HelloPeterClient**: Handles API requests to HelloPeter to fetch unreplied reviews.
- **BulkSMSClient**: Manages sending SMS messages through the BulkSMS API.
- **Debugger**: Provides logging functionality for debugging purposes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.