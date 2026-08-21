// Mock data for the single-animal Health Record detail page (mockup).
// Resolved by animal id so the health-records drawer can deep-link here.

const JACKAL = {
  id: "ANM-00045",
  name: "Jackal",
  species: "Dog",
  adoptionStatus: "Available for Adoption",
  breed: "Aspin",
  gender: "Male",
  age: "2 years",
  location: "Mati City Pound",
  rescuedOn: "April 12, 2025",
  photo: null,
  overview: {
    status: "Healthy",
    statusTone: "green",
    vaccination: "Up to date",
    vaccinationTone: "blue",
    deworming: "Up to date",
    dewormingTone: "purple",
    neutered: "Yes",
    neuteredTone: "orange",
    notes:
      "Jackal is in good health. Regular check-ups recommended every 6 months.",
    notesMeta: "Last updated: May 20, 2025 by Dr. Ana Reyes",
  },
  history: [
    {
      date: "May 20, 2025",
      doctor: "Dr. Ana Reyes",
      title: "Regular Check-up",
      description: "General physical examination – Normal",
      tone: "green",
    },
    {
      date: "May 20, 2025",
      doctor: "Dr. Ana Reyes",
      title: "Rabies Vaccination",
      description: "Annual rabies vaccine administered",
      tone: "blue",
    },
    {
      date: "Mar 12, 2025",
      doctor: "Dr. Paulo Mendoza",
      title: "Deworming",
      description: "Deworming tablet given",
      tone: "purple",
    },
    {
      date: "Jan 18, 2025",
      doctor: "Dr. Ana Reyes",
      title: "Neutering",
      description: "Neutering procedure completed",
      tone: "orange",
    },
    {
      date: "Aug 05, 2024",
      doctor: "Dr. Maria Cruz",
      title: "Skin Treatment",
      description: "Treated for mild dermatitis",
      tone: "red",
    },
  ],
  vaccinations: [
    {
      vaccine: "DHPP",
      dateGiven: "May 20, 2025",
      nextDue: "May 20, 2026",
      status: "Completed",
    },
    {
      vaccine: "Rabies",
      dateGiven: "May 20, 2025",
      nextDue: "May 20, 2026",
      status: "Completed",
    },
    {
      vaccine: "Bordetella",
      dateGiven: "May 20, 2025",
      nextDue: "May 20, 2026",
      status: "Completed",
    },
  ],
  reminders: [
    {
      title: "Deworming",
      dueDate: "Sep 16, 2026",
      days: 26,
      tone: "yellow",
      icon: "tablets",
    },
    {
      title: "DHPP Vaccine",
      dueDate: "Mar 21, 2027",
      days: 305,
      tone: "blue",
      icon: "syringe",
    },
    {
      title: "Rabies Vaccine",
      dueDate: "Apr 21, 2027",
      days: 366,
      tone: "blue",
      icon: "syringe",
    },
  ],
  vitals: [
    {
      label: "Weight",
      value: "15.2",
      unit: "kg",
      spark: [14.6, 14.8, 15.0, 15.1, 15.2, 15.2, 15.2],
    },
    {
      label: "Body Temperature",
      value: "38.6",
      unit: "°C",
      spark: [38.9, 38.7, 38.6, 38.8, 38.6, 38.5, 38.6],
    },
    {
      label: "Heart Rate",
      value: "102",
      unit: "bpm",
      spark: [110, 106, 104, 101, 103, 102, 102],
    },
    {
      label: "Respiratory Rate",
      value: "24",
      unit: "bpm",
      spark: [28, 26, 25, 24, 25, 24, 24],
    },
  ],
  vitalMeta: "Recorded on: May 20, 2025",
  documents: [
    { name: "Medical Certificate", meta: "May 20, 2025" },
    { name: "Vaccination Card", meta: "May 20, 2025" },
    { name: "Neutering Certificate", meta: "Jan 18, 2025?" },
  ],
  stats: [
    { number: "12", label: "Check-ups Completed", tone: "green", icon: "stethoscope" },
    { number: "18", label: "Vaccinations Given", tone: "blue", icon: "syringe" },
    { number: "10", label: "Deworming Given", tone: "purple", icon: "tablets" },
    { number: "8", label: "Neutering Procedures", tone: "orange", icon: "scissors" },
  ],
};

const MOCK_DB = {
  "ANM-00045": JACKAL,
};

function fallback(id) {
  const name = id && id.startsWith("ANM-") ? `Animal ${id.slice(4)}` : "Unknown";
  const base = structuredClone(JACKAL);
  base.id = id || "ANM-00000";
  base.name = name;
  return base;
}

export function getHealthRecord(id) {
  if (!id) return JACKAL;
  return MOCK_DB[id] || fallback(id);
}
