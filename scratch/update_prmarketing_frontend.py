import os
import shutil

# 1. Update website/app/startup-stories/page.tsx (Remove 2 blogs)
page_path = r"C:\hk\prmarketing\website\app\startup-stories\page.tsx"

page_content = """import type { Metadata } from "next";
import Reveal from "@/components/Reveal";
import CtaBand from "@/components/CtaBand";
import { multiBreadcrumbSchema } from "@/lib/seo";
import { IconSparkles } from "@/components/icons";
import StoriesFilterView, { StoryItem } from "@/components/StoriesFilterView";

export const metadata: Metadata = {
  title: "Startup Stories & Growth Intelligence | PR Marketing Ventures",
  description:
    "Exclusive startup growth blueprints, venture scaling strategies, unit economics models, and Generative Engine Optimization frameworks authored by PR Marketing Ventures.",
  alternates: { canonical: "/startup-stories/" },
  openGraph: {
    title: "Startup Stories & Growth Intelligence | PR Marketing Ventures",
    description:
      "Exclusive startup growth blueprints, venture scaling strategies, and B2B growth frameworks.",
  },
};

const initialStories: StoryItem[] = [
  {
    slug: "multi-touch-attribution-cac-payback-optimization-2026",
    title: "Multi-Touch Attribution & CAC Payback Optimization 2026",
    desc: "This guide details how transitioning from single-touch to multi-touch attribution (MTA) directly optimizes Customer Acquisition Cost (CAC) payback periods for high-growth tech companies.",
    tag: "Growth Metrics",
    category: "Marketing Strategy",
    readTime: "9 min read",
    image: "/images/guides/multi-touch-attribution-cac-payback-optimization-2026.jpg",
  },
  {
    slug: "2026-seed-pitch-deck-architecture-capital-dynamics",
    title: "2026 Seed Pitch Deck Architecture: Winning Capital Dynamics",
    desc: "This guide defines the structural evolution of the seed pitch deck for 2026, emphasizing a 12-15 slide framework that balances narrative clarity with hard unit economics.",
    tag: "Venture Strategy",
    category: "Startup & Innovation",
    readTime: "9 min read",
    image: "/images/guides/2026-seed-pitch-deck-architecture-capital-dynamics.jpg",
  },
  {
    slug: "b2b-abm-high-ticket-lead-scoring-2026",
    title: "B2B ABM & High-Ticket Lead Scoring Strategy 2026",
    desc: "This strategic guide outlines the convergence of Account-Based Marketing and advanced lead scoring for high-value B2B deals in 2026 with intent data integration.",
    tag: "B2B Growth",
    category: "Marketing Strategy",
    readTime: "9 min read",
    image: "/images/guides/b2b-abm-high-ticket-lead-scoring-2026.jpg",
  },
  {
    slug: "ai-agent-workflows-autonomous-operations-startups-2026",
    title: "AI Agent Workflows & Autonomous Ops for Startups in 2026",
    desc: "In 2026, AI agents have evolved into autonomous orchestrators capable of planning and executing complex multi-step workflows for high-growth startups.",
    tag: "AI Operations",
    category: "Startup & Innovation",
    readTime: "9 min read",
    image: "/images/guides/ai-agent-workflows-autonomous-operations-startups-2026.jpg",
  },
];

export default function StartupStoriesHubPage() {
  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify([
            multiBreadcrumbSchema([
              { name: "Home", path: "/" },
              { name: "Startup Stories", path: "/startup-stories/" },
            ]),
          ]),
        }}
      />

      <section className="bg-gradient-to-b from-[#f9f6f0] via-white to-white py-16 sm:py-24">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <Reveal>
            <div className="max-w-3xl">
              <span className="inline-flex items-center gap-1.5 rounded-full bg-primary-soft px-3.5 py-1 text-xs font-semibold text-primary">
                <IconSparkles width={14} height={14} />
                VENTURE INSIGHTS & STRATEGY
              </span>
              <h1 className="mt-4 font-heading text-3xl font-bold tracking-tight text-ink sm:text-5xl">
                Startup Stories & Growth Intelligence
              </h1>
              <p className="mt-4 text-base text-slate-600 sm:text-lg">
                Explore in-depth venture blueprints, AI innovation models, high-velocity talent strategies, and unit economics breakdowns authored by the growth engineers at PR Marketing Ventures.
              </p>
            </div>
          </Reveal>

          {/* Interactive Live Database Synchronized View */}
          <StoriesFilterView stories={initialStories} />
        </div>
      </section>

      <CtaBand />
    </>
  );
}
"""

with open(page_path, "w", encoding="utf-8") as f:
    f.write(page_content.strip())
print(f"Updated {page_path}")


# 2. Update website/components/StoriesFilterView.tsx (Add live client-side DB sync)
filter_path = r"C:\hk\prmarketing\website\components\StoriesFilterView.tsx"

filter_content = """"use client";

import { useState, useEffect } from "react";
import Link from "next/link";
import { IconSparkles, IconArrowRight } from "@/components/icons";

export interface StoryItem {
  slug: string;
  title: string;
  desc: string;
  tag: string;
  category: "Startup & Innovation" | "Marketing Strategy" | string;
  readTime: string;
  image?: string;
}

interface StoriesFilterViewProps {
  stories: StoryItem[];
}

export default function StoriesFilterView({ stories: initialStories }: StoriesFilterViewProps) {
  const [stories, setStories] = useState<StoryItem[]>(initialStories);
  const [activeCategory, setActiveCategory] = useState<string>("All");
  const [searchQuery, setSearchQuery] = useState<string>("");

  // Live Database Sync on Client Mount
  useEffect(() => {
    async function fetchLiveStories() {
      try {
        const res = await fetch("/backend/api/v1/index.php/posts");
        if (!res.ok) return;
        const json = await res.json();
        if (json && json.success && Array.isArray(json.data) && json.data.length > 0) {
          const mapped: StoryItem[] = json.data
            .filter((p: any) => p.status === "Published" || !p.status)
            .map((p: any) => ({
              slug: p.slug,
              title: p.title,
              desc: p.summary || p.desc || "",
              tag: p.tag || p.category_name || "Venture Intel",
              category: p.category_name || "Startup & Innovation",
              readTime: p.reading_time || "9 min read",
              image: p.cover_image || `/images/guides/${p.slug}.jpg`
            }));
          
          if (mapped.length > 0) {
            setStories(mapped);
          }
        }
      } catch (e) {
        // Fallback gracefully to pre-rendered initialStories
      }
    }

    fetchLiveStories();
  }, []);

  const categories = [
    { label: "All Stories", value: "All", count: stories.length },
    {
      label: "Startup & Innovation",
      value: "Startup & Innovation",
      count: stories.filter(
        (s) =>
          s.category === "Startup & Innovation" ||
          s.tag.toLowerCase().includes("startup") ||
          s.tag.toLowerCase().includes("innovation")
      ).length,
    },
    {
      label: "Marketing Strategy",
      value: "Marketing Strategy",
      count: stories.filter(
        (s) =>
          s.category === "Marketing Strategy" ||
          s.tag.toLowerCase().includes("strategy") ||
          s.tag.toLowerCase().includes("marketing") ||
          s.tag.toLowerCase().includes("growth")
      ).length,
    },
  ];

  const filteredStories = stories.filter((s) => {
    // Category match
    let matchesCategory = true;
    if (activeCategory === "Startup & Innovation") {
      matchesCategory =
        s.category === "Startup & Innovation" ||
        s.tag.toLowerCase().includes("startup") ||
        s.tag.toLowerCase().includes("innovation");
    } else if (activeCategory === "Marketing Strategy") {
      matchesCategory =
        s.category === "Marketing Strategy" ||
        s.tag.toLowerCase().includes("strategy") ||
        s.tag.toLowerCase().includes("marketing") ||
        s.tag.toLowerCase().includes("growth");
    } else if (activeCategory !== "All") {
      matchesCategory = s.category === activeCategory;
    }

    // Search match
    const q = searchQuery.toLowerCase().trim();
    const matchesSearch = !q || s.title.toLowerCase().includes(q) || s.desc.toLowerCase().includes(q);

    return matchesCategory && matchesSearch;
  });

  return (
    <div className="mt-8">
      {/* Category Filter Tabs Bar & Live Search */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200/80 pb-6">
        <div className="flex flex-wrap items-center gap-2 sm:gap-3">
          {categories.map((cat) => {
            const isActive = activeCategory === cat.value;
            return (
              <button
                key={cat.value}
                onClick={() => setActiveCategory(cat.value)}
                className={`inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-xs sm:text-sm font-bold transition-all duration-200 ${
                  isActive
                    ? "bg-[#1f140e] text-[#d4af37] shadow-md shadow-black/10 scale-105"
                    : "bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900"
                }`}
              >
                <span>{cat.label}</span>
                <span
                  className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${
                    isActive
                      ? "bg-[#d4af37]/20 text-[#d4af37]"
                      : "bg-slate-200/80 text-slate-500"
                  }`}
                >
                  {cat.count}
                </span>
              </button>
            );
          })}
        </div>

        {/* Live Search Input */}
        <div className="relative w-full sm:w-64">
          <input
            type="text"
            placeholder="Search stories..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-900 outline-none transition focus:border-amber-700 focus:ring-1 focus:ring-amber-700"
          />
        </div>
      </div>

      {/* Stories Grid */}
      <div className="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        {filteredStories.map((story) => (
          <article
            key={story.slug}
            className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl hover:shadow-black/5"
          >
            <div>
              <div className="flex items-center justify-between gap-2">
                <span className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                  {story.tag}
                </span>
                <span className="text-xs font-medium text-slate-400">
                  {story.readTime}
                </span>
              </div>

              <h2 className="mt-4 font-heading text-xl font-bold tracking-tight text-slate-900 group-hover:text-amber-800 transition-colors duration-200 line-clamp-2">
                <Link href={`/startup-stories/${story.slug}/`}>
                  {story.title}
                </Link>
              </h2>

              <p className="mt-3 text-sm text-slate-600 line-clamp-3 leading-relaxed">
                {story.desc}
              </p>
            </div>

            <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
              <Link
                href={`/startup-stories/${story.slug}/`}
                className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-900 group-hover:text-amber-800 transition-colors"
              >
                <span>Read Full Story</span>
                <IconArrowRight width={14} height={14} className="transition-transform group-hover:translate-x-1" />
              </Link>
            </div>
          </article>
        ))}
      </div>

      {filteredStories.length === 0 && (
        <div className="mt-12 text-center py-12 rounded-2xl border border-dashed border-slate-200 bg-slate-50">
          <p className="text-sm font-semibold text-slate-500">No stories found matching your filter criteria.</p>
        </div>
      )}
    </div>
  );
}
"""

with open(filter_path, "w", encoding="utf-8") as f:
    f.write(filter_content.strip())
print(f"Updated {filter_path}")


# 3. Clean up deleted static folders if present
folders_to_delete = [
    r"C:\hk\prmarketing\website\app\startup-stories\omnichannel-d2c-retention-clv-2026",
    r"C:\hk\prmarketing\website\app\startup-stories\product-led-growth-metrics-onboarding-velocity-2026",
    r"C:\hk\prmarketing\website\out\startup-stories\omnichannel-d2c-retention-clv-2026",
    r"C:\hk\prmarketing\website\out\startup-stories\product-led-growth-metrics-onboarding-velocity-2026"
]

for folder in folders_to_delete:
    if os.path.exists(folder):
        shutil.rmtree(folder, ignore_errors=True)
        print(f"Deleted static folder: {folder}")

print("Frontend update complete!")
