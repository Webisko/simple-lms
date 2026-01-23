# Time-Limited Course Access

Simple LMS allows you to grant course access for a limited time (with expiration date) or indefinitely.

## How It Works

- Course access is stored in `user_meta` as a list of course IDs (access tags).
- Optionally, you can set an expiration date for access per course.
- When access expires, Simple LMS treats it as invalid and removes the access record for that course.

## Setting Expiration Date (Admin Panel)

1. Go to user editing in WordPress (Users → User Profile / Edit User).
2. In the Simple LMS Access section, select the course the user should have access to.
3. Set the date in the **Set expiration date** or **Change expiration date** field.
4. Save the user profile.

To remove the time limit (grant indefinite access), clear the date field or use the **Remove limit** button and save the profile.

## For Developers (Meta Keys)

- List of courses with access:
  - `simple_lms_course_access` (array of course IDs)
- Access expiration per course:
  - `simple_lms_course_access_expiration_{course_id}` (timestamp)
- (Optional) Access start date / drip schedule start:
  - `simple_lms_course_access_start_{course_id}` (timestamp GMT)

## Notes

- If you use WooCommerce: course purchase can automatically grant access; you can still set a time limit manually in the user profile.
- Expiration is evaluated against server time (`current_time('timestamp')`).
