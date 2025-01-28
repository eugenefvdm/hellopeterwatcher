# Hello Peter Watcher

Hello Peter Watcher is a PHP application designed to monitor reviews on HelloPeter and send notifications for unreplied reviews using BulkSMS, Slack, and Telegram services.

## Features

- **Fetch Unreplied Reviews**: Connects to the HelloPeter API to retrieve reviews that have not been replied to.
- **Send Notifications**: Uses the BulkSMS, Slack, and Telegram APIs to send notifications about unreplied reviews to specified recipients.
- **Environment Configuration**: Utilizes environment variables for secure configuration management.
- **State Management**: Maintains review state to prevent duplicate notifications and track review history.

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
   - Fill in your HelloPeter API key, BulkSMS credentials, Slack webhook URL, and Telegram bot token in the `.env` file.

## Usage

1. **Run the Application**:
   ```bash
   php index.php
   ```

2. **Functionality**:
   - The application will fetch unreplied reviews from HelloPeter.
   - It will output the count and details of these reviews.
   - If there are unreplied reviews, it will send notifications via SMS, Slack, and Telegram to the recipients specified in the `.env` file.

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
- **SlackClient**: Manages sending notifications through Slack.
- **TelegramClient**: Manages sending notifications through Telegram.
- **Debugger**: Provides logging functionality for debugging purposes.
- **StateManager**: Maintains the application state by:
  - Tracking previously notified reviews to prevent duplicate notifications
  - Storing review history in a JSON file for persistence
  - Managing review state transitions (new, notified, replied)
  - Providing methods to query and update review states

## About

This application was created by [Vander Host](https://vander.host). We specialise in servers, email & web hosting, and domain registration.

Internet access provided by [Atomic Access](https://atomic.co.za), the top fibre provider in South Africa.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.