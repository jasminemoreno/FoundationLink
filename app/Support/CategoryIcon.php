<?php

namespace App\Support;

/**
 * Picks an icon for a donation category from its name (English and Filipino).
 *
 * Rules are checked in order, so more specific ones come first ("mental health"
 * before "health", "water" before "clean"). A keyword ending in * matches any
 * word starting with it ("educat*" -> education, educational); others must be
 * the whole word, or that word plus "s" ("art"/"arts" but never "heart").
 */
class CategoryIcon
{
    /** Shown when nothing matches: helping hands suits a charity platform better than a folder. */
    public const FALLBACK = '🤝';

    /** [keywords, icon] in priority order. */
    private const RULES = [
        [['mental', 'psych*', 'counsel*'], '🧠'],
        [['disab*', 'pwd', 'wheelchair*', 'handicap*'], '♿'],
        [['elder*', 'senior*', 'aged', 'matanda', 'nakatatanda', 'lolo', 'lola', 'retire*'], '👵'],
        [['child*', 'kid', 'youth', 'bata', 'kabataan', 'orphan*', 'infant*', 'baby', 'babies', 'minor'], '🧒'],
        [['women', 'woman', 'girl', 'mother*', 'maternal', 'kababaihan', 'babae'], '👩'],
        [['emergenc*', 'rescue*', 'ambulance*'], '🚨'],
        [['disaster*', 'relief', 'calamit*', 'sakuna', 'typhoon*', 'bagyo', 'flood*', 'baha', 'earthquake*', 'lindol', 'fire', 'sunog', 'evacuat*'], '🌪️'],
        [['medic*', 'hospital*', 'clinic*', 'doctor*', 'gamot', 'surger*', 'cancer', 'blood', 'dialysis', 'patient*'], '💊'],
        [['health*', 'kalusugan', 'wellness'], '🏥'],
        [['feed*', 'food', 'hunger', 'hungry', 'meal', 'pagkain', 'gutom', 'nutrition*', 'pantry'], '🍲'],
        [['water', 'tubig', 'sanitation', 'wash'], '💧'],
        [['hous*', 'shelter*', 'home', 'pabahay', 'tirahan', 'bahay'], '🏠'],
        [['povert*', 'kahirapan', 'mahirap', 'poor', 'needy', 'indigent*', 'homeless'], '🤲'],
        [['livelihood*', 'kabuhayan', 'job', 'employ*', 'skill', 'entrepreneur*', 'income', 'trabaho'], '💼'],
        [['scholar*', 'educat*', 'school*', 'edukasyon', 'learn*', 'literacy', 'student*', 'paaralan', 'tutor*', 'book', 'eskwela*'], '🎓'],
        [['tree', 'forest*', 'reforest*', 'planting', 'mangrove*', 'puno'], '🌳'],
        [['ocean*', 'sea', 'marine', 'coast*', 'beach*', 'dagat', 'river'], '🌊'],
        [['environment*', 'kalikasan', 'climate', 'green', 'ecolog*', 'nature', 'recycl*', 'clean*', 'kapaligiran'], '🌱'],
        [['animal*', 'hayop', 'pet', 'dog', 'cat', 'wildlife', 'stray', 'aso', 'pusa', 'veterinar*'], '🐶'],
        [['sport*', 'athlet*', 'fitness', 'basketball', 'football', 'palaro'], '⚽'],
        [['art', 'artist*', 'culture', 'cultural', 'music*', 'heritage', 'sining', 'kultura'], '🎨'],
        [['faith', 'church*', 'religio*', 'mission*', 'ministr*', 'simbahan', 'spiritual*', 'bible', 'parish*'], '⛪'],
        [['technolog*', 'digital', 'computer*', 'internet', 'tech'], '💻'],
        [['cloth*', 'apparel', 'damit', 'uniform*'], '👕'],
        [['communit*', 'komunidad', 'barangay', 'village*', 'neighborhood*', 'social', 'civic', 'bayanihan'], '🏘️'],
    ];

    /** Icons the super admin can choose from in the category form. */
    public const CHOICES = [
        '🎓', '🏥', '💊', '🧠', '🍲', '💧', '🏠', '🤲', '💼', '🧒', '👵', '👩',
        '♿', '🐶', '🌱', '🌳', '🌊', '🌪️', '🚨', '🏘️', '⚽', '🎨', '⛪', '💻',
        '👕', '❤️', '🤝', '⭐',
    ];

    public static function for(?string $name): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        foreach (self::RULES as [$keywords, $icon]) {
            foreach ($keywords as $keyword) {
                foreach ($words as $word) {
                    if (self::matches($word, $keyword)) {
                        return $icon;
                    }
                }
            }
        }

        return self::FALLBACK;
    }

    /** True if this icon is one the system would pick by itself (not a deliberate choice). */
    public static function isAutomatic(?string $icon, ?string $name): bool
    {
        return in_array($icon, [null, '', '📁', self::FALLBACK, self::for($name)], true);
    }

    private static function matches(string $word, string $keyword): bool
    {
        if (str_ends_with($keyword, '*')) {
            return str_starts_with($word, rtrim($keyword, '*'));
        }

        return $word === $keyword || $word === $keyword . 's' || $word === $keyword . 'es';
    }
}
