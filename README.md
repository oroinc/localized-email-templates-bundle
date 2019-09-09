OroLocalizedEmailTemplatesBundle
================================

Table of contents
-----------------
- [Overview](#overview)
- [Installation Notes](#installation-notes)
- [Fallback Rules](#fallback-rules)

## Overview
This extension replaces the email templates per languages with the email templates per localizations. It enables you to create template localizations for inactive localizations.

## Installation Notes
Migration of existing email templates is based on the template language.
All of them are moved to email templates per existing localizations (including inactive localizations).
If a language has several localizations, the email template for that language is duplicated for each localized email template.

## Fallback Rules
You can enable fallback to the template for parent localization for each localized template field.

If the localization does not have a parent, you can enable fallback to the default template value.

**Important:** When you save the template, fields with enabled fallback options are cleared.
