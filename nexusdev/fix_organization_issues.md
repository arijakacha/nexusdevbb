# Organization System Fixes

## Critical Issues to Fix

### 1. Entity Mapping Issues

The Team entity needs to add `inversedBy="teams"` to the organization mapping:

```php
// In src/Entity/Team.php
#[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: "teams")]
#[ORM\JoinColumn(nullable: false)]
private Organization $organization;
```

### 2. Database Schema Update

Run this command to sync the database:

```bash
php bin/console doctrine:schema:update --force
```

### 3. Other Entity Fixes

Similar fixes needed for:
- Player#statistics → Statistic#player (add inversedBy="statistics")
- Player#coachingSessions → CoachingSession#player (add inversedBy="coachingSessions")
- Product#purchases → ProductPurchase#product (add inversedBy="purchases")
- User#productPurchases → ProductPurchase#user (add inversedBy="productPurchases")

## Validation Commands

```bash
# Check current status
php bin/console doctrine:schema:validate

# Update database schema
php bin/console doctrine:schema:update --force

# Clear cache
php bin/console cache:clear
```

## Testing

After fixes, test:
1. Organization creation/editing
2. Team management
3. Player recruitment
4. Public organization browsing
5. Admin organization management
