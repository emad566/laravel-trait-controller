# Changelog

All notable changes to `laravel-trait-controller` will be documented in this file.

## [Unreleased]

## [2.1.0] - 2024-01-20

### Added
- FilterRequest class for comprehensive API filtering with built-in validation
- Advanced filtering support for common e-commerce patterns (category, product, user filtering)
- Price range filtering with min/max and range object support
- Multi-column sorting with validation
- Date period filtering (today, this week, this month, this year)
- Array validation with size limits and type checking
- Enhanced security validation for all filter inputs

### Updated
- Enhanced example controller with FilterRequest usage demonstration
- Updated documentation with FilterRequest usage examples and API patterns

## [2.0.0] - 2024-01-20

### Added
- Enhanced CustomLogger with info, error, warning, debug methods and configurable logging
- BaseFormRequest (renamed from CustomFormRequest) with comprehensive security validation
- Advanced filtering capabilities in ListingTrait with multi-column sorting and relationship filtering
- Include options pattern inspired by Laravel API resources
- Comprehensive input sanitization and XSS/SQL injection prevention
- Audit logging for all operations with error tracking
- Enhanced API response structure with metadata and debugging information

### Changed
- **BREAKING**: Renamed all traits for better readability:
  - IndexTrait → ListingTrait
  - ShowTrait → RetrievalTrait
  - EditTrait → EditFormTrait
  - DestroyTrait → DeletionTrait
  - ToggleActiveTrait → StatusToggleTrait
- Enhanced ListingTrait with advanced filtering capabilities
- Improved error handling and validation
- Updated documentation with comprehensive examples

### Security
- Added comprehensive input validation and sanitization
- Implemented XSS prevention and SQL injection protection
- Added path traversal prevention
- Enhanced error responses with sensitive data masking

## [1.0.0] - 2024-01-XX

### Added
- Initial release
- IndexTrait for listing/pagination with advanced filtering
- ShowTrait for single record retrieval  
- EditTrait for editing data preparation
- DestroyTrait for record deletion with soft delete support
- ToggleActiveTrait for status toggling
- BaseController with automatic model configuration
- Comprehensive configuration system
- Query builder macros (like, likeStart, orLike)
- Validation handling with FailedValidation service
- Helper functions for customization
- Laravel 10.x and 11.x compatibility
- Extensive documentation and examples 
