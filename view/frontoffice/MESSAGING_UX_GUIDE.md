# Messaging System UX Guide

## Overview
This document outlines the UX improvements made to the private messaging system to make contacting other users obvious, contextual, and reassuring.

## Entry Points

### 1. Post Cards (`post-card-template.php`)
- **Location**: Below post content, above footer actions
- **Button**: "💬 Contacter le propriétaire"
- **Visibility**: Only shown for non-owners
- **Context**: Linked to the specific post
- **Helper Text**: "Messages privés et sécurisés"

### 2. User Profile (`profile.php`)
- **Location**: Below profile picture, above profile information
- **Button**: "💬 Contacter cet utilisateur"
- **Visibility**: Only when viewing another user's profile
- **Helper Text**: "Messages privés et sécurisés"

### 3. Messages Inbox (`messages.php`)
- **Empty State**: 
  - Clear instructions on how to start conversations
  - CTA button: "Parcourir les demandes"
  - Trust signals: "Messages privés et sécurisés • Modération par les administrateurs"

## Chat Context Banner

When viewing a conversation, a context banner appears at the top showing:
- What the conversation is about (post, donation, request)
- Link to view the related entity
- Optional: Amount for donations

This reassures users they're in the right conversation.

## Micro-Copy & Trust Signals

### Helper Text
- "Messages privés et sécurisés" - Appears below contact buttons
- "Modération par les administrateurs" - In empty state

### Button Labels
- "💬 Contacter le propriétaire" - For posts
- "💬 Contacter cet utilisateur" - For profiles
- "💬 Message Recipient" - After donation (to be added)

## Visual Design

### Contact Buttons
- Primary accent color (#f5a425)
- Full width in post cards for mobile-friendliness
- Hover animation (translateY + shadow)
- Disabled state with reason tooltip

### Empty States
- Friendly icons
- Clear, actionable instructions
- CTA buttons to guide next steps
- Trust signals at bottom

## Mobile UX

- Contact buttons are thumb-friendly (min 44px height)
- Full-width buttons in post cards
- Chat opens full-screen on mobile
- Context banner scrolls with messages

## Error Prevention

When contact is not possible:
- Button is disabled
- Tooltip shows reason
- Helper text suggests next step
- Example: "Vous pouvez contacter après avoir fait un don"

## Implementation Files

1. **Component**: `view/frontoffice/components/contact-button.php`
   - Reusable contact button component
   - Handles conversation creation
   - Shows loading states

2. **Views**:
   - `messages.php` - Inbox and chat with context banner
   - `post-card-template.php` - Contact button for posts
   - `profile.php` - Contact button for profiles

3. **API**: `controllers/api/message.php`
   - `get_conversation_context` - Returns entity info for context banner

## Future Enhancements

1. Add "Message Recipient" button after donation confirmation
2. Add tooltip for first-time users
3. Add donation request page contact buttons
4. Show conversation count in user profile
5. Add "Recent contacts" quick access





