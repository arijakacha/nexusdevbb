# Jitsi Meet Integration for Coaching Sessions

This feature allows coaches to automatically create and join video meetings for their coaching sessions using Jitsi Meet API.

## 🚀 Features

- **Automatic Meeting Creation**: Meetings are created 15 minutes before session time
- **One-Click Join**: Direct meeting links for coaches and players
- **Meeting Management**: Create, join, and manage meetings from session details
- **Security**: JWT-protected meetings with expiration times
- **Real-time Status**: Live meeting status updates

## 📋 Setup Instructions

### 1. Configure API Key
Your Jitsi API key is already configured in `config/services.yaml`:
```yaml
parameters:
    jitsi_api_key: 'b29021'
```

### 2. Database Migration
The database migration has been applied to add meeting fields to the `coaching_session` table.

### 3. Automatic Meeting Creation (Cron Job)

Set up a cron job to run every 5 minutes to automatically create meetings for upcoming sessions:

```bash
# Edit crontab
crontab -e

# Add this line (runs every 5 minutes)
*/5 * * * * cd /path/to/your/project && php bin/console app:create-meetings >> /var/log/jitsi-meetings.log 2>&1
```

### 4. Manual Meeting Creation

Coaches can also create meetings manually:

1. Go to **BCoach** → **Session Details**
2. Click **"Create Meeting"** button
3. Meeting is created instantly with a 2-hour expiration
4. Click **"Join Meeting"** to open the video call

## 🎯 How It Works

### Automatic Process
1. **Every 5 minutes**: The cron job runs `app:create-meetings` command
2. **15 minutes before session**: Meetings are automatically created
3. **2-hour expiration**: Meetings expire after 2 hours for security
4. **Unique rooms**: Each session gets a unique room name

### Manual Process
1. **Coach clicks "Create Meeting"**: API call to Jitsi creates room
2. **JWT token generated**: Secure authentication for the meeting
3. **Meeting URL stored**: Saved in database with expiration time
4. **Join button appears**: Direct link to video meeting

## 🔧 Technical Details

### API Configuration
- **Base URL**: `https://8x8.vc/vpaas-magic-cookie-d608256e812940009ae4a0a4573a8a70`
- **Authentication**: JWT tokens with HMAC-SHA256
- **Room naming**: `nexus-coach-{coach}-{player}-{session-id}-{timestamp}`

### Database Fields
- `meeting_url`: Full Jitsi meeting URL with JWT token
- `meeting_room`: Room name for reference
- `meeting_expires_at`: Meeting expiration timestamp

### Security Features
- **JWT Authentication**: All meetings require valid JWT tokens
- **Time-based Expiration**: Meetings automatically expire
- **Unique Room Names**: Prevents meeting conflicts
- **Role-based Access**: Only coaches can create meetings for their sessions

## 🎨 User Interface

### Session Card (Dashboard)
- Shows **"Join Meeting"** button when meeting is active
- Real-time status updates every 30 seconds

### Session Details Page
- **Meeting Status**: Active/Expired/Not Created
- **Create Meeting**: Manual meeting creation
- **Join Meeting**: Direct link to video call
- **Copy Link**: Share meeting URL with player
- **Expiration Time**: Shows when meeting expires

## 📱 Usage Flow

### For Coaches
1. **Session scheduled** → Automatic meeting created 15 min before
2. **Or manual creation** → Click "Create Meeting" anytime
3. **Join meeting** → Click "Join Meeting" button
4. **Share with player** → Click "Copy Link" to send URL

### For Players
1. **Receive meeting URL** from coach
2. **Click link** → Opens Jitsi meeting in browser
3. **Join video call** → No registration required

## 🛠️ Commands

### Create Meetings Manually
```bash
# Create meetings for upcoming sessions
php bin/console app:create-meetings

# Check command help
php bin/console app:create-meetings --help
```

### Database Migration
```bash
# If you need to re-run migration
php bin/console doctrine:migrations:migrate
```

## 🔍 Monitoring

### Log Files
- **Cron job logs**: `/var/log/jitsi-meetings.log`
- **Application logs**: `var/log/dev.log` or `var/log/prod.log`

### Debugging
- Check browser console for JavaScript errors
- Verify API key configuration
- Check database for meeting fields
- Test manual meeting creation first

## 🚨 Troubleshooting

### Common Issues

1. **Meeting not created**
   - Check API key configuration
   - Verify Jitsi service availability
   - Check cron job logs

2. **Meeting link not working**
   - Verify JWT token is valid
   - Check meeting expiration time
   - Test with different browser

3. **Cron job not running**
   - Verify cron syntax
   - Check file permissions
   - Review system logs

### Error Messages
- `"Meeting has expired"`: Create a new meeting
- `"No meeting created yet"`: Click "Create Meeting" button
- `"Failed to create meeting"`: Check API configuration

## 📞 Support

For issues with Jitsi integration:
1. Check this documentation
2. Review application logs
3. Test manual meeting creation
4. Verify API key and service status

---

**Note**: This integration uses Jitsi Meet as a Service (JaaS) with your provided API key. Ensure your Jitsi plan supports the required features.
