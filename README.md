# Power: The Game - Multiplayer Web Version

A web-based multiplayer implementation of the classic 1995 strategy game "Power: The Game". This version uses HTML, CSS, JavaScript, and vanilla PHP with MySQL for a complete online multiplayer experience.

## Game Overview

Power: The Game is a simultaneous-turn strategy game where 2-4 players compete for global domination on a stylized world map. Players command armies of Infantry, Tanks, Fighters, and Destroyers (plus their upgraded versions) to capture enemy Flags and control the battlefield.

### Key Features

- **Multiplayer Support**: 2-4 players per game
- **Real-time Gameplay**: Timed rounds with simultaneous command submission
- **Command System**: Issue up to 5 commands per round
- **Power Economy**: Earn Power Units by occupying enemy territory
- **Unit Upgrades**: Combine 3 identical units into powerful Group II units
- **MegaMissile**: Devastating weapon that destroys all units in a sector
- **Dual Victory Conditions**: Win by capturing all Flags or having the most power when time expires

## Requirements

- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: 7.4 or higher
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Web Browser**: Modern browser with JavaScript enabled

## Installation Instructions

### Step 1: Set Up Your LAMP Server

Make sure you have a working LAMP (Linux, Apache, MySQL, PHP) server. If you're on Ubuntu/Debian:

```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql libapache2-mod-php
```

### Step 2: Create the Database

1. Log into MySQL:
```bash
mysql -u root -p
```

2. Create the database:
```sql
CREATE DATABASE power_game CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Create a database user (recommended):
```sql
CREATE USER 'power_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON power_game.* TO 'power_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

4. Import the database schema:
```bash
mysql -u root -p power_game < database.sql
```

### Step 3: Install the Application

1. Copy all files to your web directory:
```bash
sudo cp -r /path/to/power /var/www/html/power
```

2. Set proper permissions:
```bash
sudo chown -R www-data:www-data /var/www/html/power
sudo chmod -R 755 /var/www/html/power
```

### Step 4: Configure the Application

1. Edit `config.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'power_game');
define('DB_USER', 'power_user');  // Change to your MySQL user
define('DB_PASS', 'your_secure_password');  // Change to your password
```

2. Update the site URL if needed:
```php
define('SITE_URL', 'http://localhost/power');  // Change to your URL
```

### Step 5: Enable Apache mod_rewrite (Optional but Recommended)

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 6: Test the Installation

1. Open your web browser and navigate to:
   - `http://localhost/power` (or your configured URL)

2. You should see the login page. Create an account to get started!

## Game Rules Summary

### Objective

Capture all enemy Flags OR have the highest total power when the game time expires.

### Map Layout

- **4 Countries**: Each with 9 sectors and a Headquarters (HQ) containing a Flag
- **5 Islands**: Strategic bridges between continents
- **12 Sea Lanes**: Water routes for naval vessels
- **4 Reserve Areas**: Off-board storage for units

### Units

| Unit | Power | Moves | Special |
|------|-------|-------|---------|
| Infantry | 20 | 2 | Can capture Flags |
| Regiment | 60 | 2 | Can capture Flags (upgraded Infantry) |
| Tank | 30 | 3 | Ground assault |
| Heavy Tank | 90 | 3 | Upgraded Tank |
| Fighter | 25 | 5 | Air superiority, flies over Islands |
| Bomber | 75 | 5 | Upgraded Fighter |
| Destroyer | 10 | 1 | Naval warfare |
| Cruiser | 50 | 1 | Upgraded Destroyer |
| Power Unit | 1 | 0 | Currency for purchases |
| MegaMissile | 0 | 0 | Destroys all units in target sector |

### Gameplay Flow

1. **Command Phase** (1-2 minutes): All players simultaneously issue up to 5 commands
2. **Resolution Phase**: All commands execute at once:
   - Movement is implemented
   - Ties are resolved (bouncing)
   - MegaMissiles launch
   - Battles are resolved
   - Captured pieces move to victor's Reserve
   - Power Units are collected
   - Flag captures are processed

### Commands

1. **Move**: Move a unit from one sector to another
2. **Upgrade**: Exchange 3 identical Group I units for 1 Group II unit
3. **Buy**: Purchase new units with Power Units
4. **Create MegaMissile**: Sacrifice 100 power worth of units to create a MegaMissile

### Combat Rules

- **Power Wins**: Highest total power value in a sector wins
- **Ties "Bounce"**: Equal power causes attacking forces to retreat
- **Capturing**: Winner takes all defeated units
- **MegaMissiles**: Destroy everything in target sector (including friendly units!)

### Economy

- Earn 1 Power Unit per enemy country occupied per round
- Spend Power Units to purchase new units:
  - Infantry: 2 Power Units
  - Tank: 3 Power Units
  - Fighter: 3 Power Units
  - Destroyer: 2 Power Units

### Strategic Concepts

1. **Island Stops**: Ground units must stop for one turn when entering/exiting Islands or HQs
2. **Flag Capture**: Only Infantry and Regiment can capture Flags
3. **Snowball Effect**: Capturing enemy units is more efficient than buying new ones
4. **Command Economy**: With only 5 commands per round, prioritization is crucial

## Troubleshooting

### Database Connection Errors

- Check `config.php` has correct database credentials
- Verify MySQL service is running: `sudo systemctl status mysql`
- Check user permissions: `SHOW GRANTS FOR 'power_user'@'localhost';`

### Permission Errors

```bash
sudo chown -R www-data:www-data /var/www/html/power
sudo chmod -R 755 /var/www/html/power
```

### Can't Login or Register

- Check PHP error log: `sudo tail -f /var/log/apache2/error.log`
- Verify database tables were created: `mysql -u root -p power_game -e "SHOW TABLES;"`

### Page Not Loading CSS/JS

- Clear browser cache
- Check file paths in HTML match actual file locations
- Verify Apache can access the `css/` and `js/` directories

## Development Roadmap

### Current Version (v1.0)

- [x] User authentication
- [x] Game lobby
- [x] Basic game board
- [x] Command system foundation
- [x] Player management

### Planned Features (v2.0)

- [ ] Complete battle resolution engine
- [ ] AI opponents
- [ ] Turn-by-turn battle animations
- [ ] Chat system
- [ ] Game replay system
- [ ] Tournament mode
- [ ] Mobile responsive design
- [ ] WebSocket for real-time updates

## Credits

**Original Game**: Power: The Game (1995) by Power Games International, Inc.

**Web Implementation**: Created as a multiplayer tribute to the classic strategy game.

## License

This is a fan-made educational project. The original "Power: The Game" and all related trademarks are property of their respective owners.

## Support

For issues, questions, or contributions, please open an issue in the GitHub repository.

---

**Enjoy conquering the world!** ⚔️
