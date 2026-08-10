export type EventPhase = 'upcoming' | 'ongoing' | 'finished';

export type EventEditionCard = {
    id: number;
    name: string;
    year: number;
    event_date: string;
    city: string;
    state: string | null;
    country: string;
    phase: EventPhase;
    event: {
        name: string;
        slug: string;
        cover_url: string | null;
        sport: {
            name: string;
            slug: string;
        };
    };
    distances: string[];
};

export type LegacyProfilePreview = {
    username: string;
    name: string;
    bio?: string | null;
    city: string | null;
    country: string | null;
    sport: string | null;
    photo_url: string | null;
    cover_url?: string | null;
    medals_count: number;
    medals: Array<{
        title: string;
        distance_label: string | null;
    }>;
};

export type EventRacePreview = {
    id: number;
    name: string;
    distance_value: string | number | null;
    distance_unit: string | null;
    start_time: string | null;
};

export type EventEditionDetail = {
    name: string;
    year: number;
    event_date: string;
    city: string;
    state: string | null;
    country: string;
    phase: EventPhase;
    registration_open_at: string | null;
    registration_close_at: string | null;
    races: EventRacePreview[];
};

export type EventDetail = {
    name: string;
    slug: string;
    description: string | null;
    cover_url: string | null;
    sport: string;
    organizer: string | null;
};

export type LegacyCodePlate = {
    athlete_name: string | null;
    event_name: string | null;
    race_name: string | null;
    official_time: string | null;
    pace: string | null;
    event_date: string | null;
    status: string;
};

export type LegacyCodeAthlete = {
    username: string;
    city: string | null;
    sport: string | null;
};
