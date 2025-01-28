# Hello Peter Watcher

Hello Peter Watcher is a PHP application that monitors unreplied reviews at HelloPeter and sends a notification count if there are new ones to BulkSMS, Telegram, or Slack.

## Features

- **Fetch Unreplied Reviews**: Connects to the HelloPeter API to retrieve reviews that have not been replied to.
- **Send Notifications**: Uses the BulkSMS, Telegram, and Slack APIs to send notifications about unreplied reviews to specified recipients.
- **Environment Configuration**: Utilizes environment variables for secure configuration management and enabling of services.
- **State Management**: Maintains review state using file based access to prevent duplicate notifications.

## Installation

1. **Clone the Repository**: 
   ```bash
   git clone https://github.com/eugenefvdm/hellopeterwatcher
   cd hellopeterwatcher
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment Variables**:
   - Copy `.env.example` to `.env`.
   - Fill in your HelloPeter API key, BulkSMS credentials, Telegram bot token and chat ID, or Slack webhook URL in the `.env` file. Enable/disable services that you require.

## Usage

1. **Run the Application**:
   ```bash
   php index.php
   ```

2. **Functionality**:
   - The application will fetch unreplied reviews from HelloPeter.
   - It will output the count and details of these reviews.
   - If there are unreplied reviews, it will send notifications via SMS, Telegram, and Slack to the recipients specified in the `.env` file.

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
- **TelegramClient**: Manages sending notifications through Telegram.
- **SlackClient**: Manages sending notifications through Slack.
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