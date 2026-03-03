# SMS Meeting Notifications Setup

This feature automatically sends meeting links to players via SMS when coaches create video meetings.

## 🎯 Features

- **Automatic SMS**: Meeting links sent when meeting is created
- **Player Consent**: Only sends to players who opt-in
- **Professional Messages**: Clean, branded SMS content
- **Error Handling**: Graceful fallback if SMS fails
- **Phone Validation**: Proper phone number formatting

## 📋 Setup Instructions

### 1. Create Twilio Account

1. **Sign up** at [twilio.com](https://www.twilio.com/try-twilio)
2. **Get free trial** - $15 credit when you sign up
3. **Verify your phone number** (required for trial)

### 2. Get Twilio Credentials

From your Twilio Console:
- **Account SID**: Found in Console Dashboard
- **Auth Token**: Click "Show" in Console Dashboard  
- **Phone Number**: Buy a number from Phone Numbers > Buy a Number

### 3. Configure Environment Variables

Add these to your `.env.local` file:

```bash
# Twilio SMS Configuration
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1your_twilio_phone_number
```

**Important**: Use `.env.local` (not `.env`) for security!

### 4. Update Player Profiles

Players need:
1. **Phone number** in their profile
2. **SMS consent** enabled

Add phone numbers to players in the database or through admin interface.

## 🎮 How It Works

### When Coach Creates Meeting:
1. **Meeting is created** with Jitsi
2. **SMS is automatically sent** if player has:
   - Phone number on file
   - SMS consent enabled
3. **Player receives SMS** with meeting link
4. **Player clicks link** to join video call

### SMS Message Format:
```
🎮 NexusPlay Coaching Session 🎥

Hi PlayerName! Your coaching session with CoachName is starting.

📅 Date: Feb 24, 2026
⏰ Time: 14:30

🔗 Join Video Call:
https://8x8.vc/vpaas-magic-cookie-.../room-name?jwt=...

Click the link or copy/paste in your browser. No registration needed!

📞 Having trouble? Contact your coach.
Powered by NexusPlay 🚀
```

## 💰 Cost Information

### Twilio Pricing (Free Tier):
- **Starting credit**: $15 USD (free trial)
- **Cost per SMS**: ~$0.08 USD (US numbers)
- **International**: Varies by country (~$0.05-0.20)

### Estimated Usage:
- **100 meetings/month** = ~$8 USD
- **500 meetings/month** = ~$40 USD
- **1000 meetings/month** = ~$80 USD

## 🔧 Testing SMS

### Test SMS Command:
```bash
php bin/console app:test-sms +1234567890
```

### Manual Test:
1. **Add phone number** to a player
2. **Enable SMS consent** for that player
3. **Create a meeting** for that player's session
4. **Check if SMS is received**

## 🛠️ Troubleshooting

### Common Issues:

1. **SMS not sending**
   - Check Twilio credentials in `.env.local`
   - Verify phone number format (+countrycode)
   - Check player has SMS consent enabled

2. **Invalid phone number**
   - Format: +1XXXXXXXXXX (US)
   - Format: +44XXXXXXXXXX (UK)
   - Must include country code

3. **Twilio errors**
   - Check Twilio Console for error logs
   - Verify account has sufficient credit
   - Ensure phone number is verified (trial accounts)

### Error Messages:
- `"Invalid phone number format"` → Check phone number format
- `"SMS sending failed"` → Check Twilio credentials/credit
- `"Player has not consented"` → Enable SMS consent for player

## 📱 Player Experience

### For Players:
1. **Receive professional SMS** with meeting details
2. **Click link** to join video call instantly
3. **No registration required** - just click and join
4. **Get all meeting info** in one message

### For Coaches:
1. **Create meeting** as usual
2. **SMS sent automatically** (no extra steps)
3. **Focus on coaching** instead of sharing links

## 🔒 Security & Privacy

- **Consent required**: Players must opt-in to SMS
- **Data protection**: Phone numbers stored securely
- **No spam**: Only sent for actual meetings
- **Easy opt-out**: Players can disable SMS anytime

## 🚀 Advanced Features

### Future Enhancements:
- **Meeting reminders** (15 min before)
- **Rescheduling notifications**
- **Custom message templates**
- **Multiple language support**
- **SMS analytics** and delivery tracking

### Customization:
Edit `src/Service/SmsService.php` to:
- Change message content
- Add branding
- Modify formatting
- Add custom fields

## 📞 Support

### Twilio Support:
- Documentation: twilio.com/docs
- Support: twilio.com/help
- Status: twilio.com/status

### Application Support:
1. Check logs: `var/log/dev.log`
2. Test with command: `php bin/console app:test-sms`
3. Verify environment variables
4. Check player phone/consent settings

---

**🎉 Your coaching platform now has professional SMS notifications!**

Players will never miss a meeting again with automatic SMS reminders containing direct meeting links. 📱🎮
