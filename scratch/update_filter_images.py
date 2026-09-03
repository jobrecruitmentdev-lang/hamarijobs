import os

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

  // Live Database Sync on Client Mount (with timestamp cache-buster)
  useEffect(() => {
    async function fetchLiveStories() {
      try {
        const res = await fetch(`/api/v1/posts?t=${Date.now()}`, {
          cache: "no-store",
          headers: { "Pragma": "no-cache" }
        });
        if (!res.ok) return;
        const json = await res.json();
        if (json && json.success && Array.isArray(json.data) && json.data.length > 0) {
          const mapped: StoryItem[] = json.data
            .filter((p: any) => p.status === "Published" || !p.status)
            .map((p: any) => ({
              slug: p.slug,
              title: p.title,
              desc: p.summary || p.desc || "",
              tag: p.tag || p.category_name || "Venture Strategy",
              category: p.category_name || "Startup & Innovation",
              readTime: p.reading_time || "9 min read",
              image: p.cover_image || `/images/guides/${p.slug}.jpg`
            }));
          
          if (mapped.length > 0) {
            setStories(mapped);
          }
        }
      } catch (e) {
        // Graceful fallback to pre-rendered initialStories
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

      {/* Stories Grid with 4K Visual Thumbnails */}
      <div className="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        {filteredStories.map((story) => (
          <article
            key={story.slug}
            className="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-slate-300 hover:shadow-xl hover:shadow-black/5"
          >
            <div>
              {/* Featured Cover Image (16:9 Aspect Ratio with Smooth Hover Zoom) */}
              <Link href={`/startup-stories/${story.slug}/`} className="block overflow-hidden rounded-xl bg-slate-100 mb-4 aspect-[16/9] relative">
                <img
                  src={story.image || `/images/guides/${story.slug}.jpg`}
                  alt={story.title}
                  className="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                  loading="lazy"
                  onError={(e) => {
                    // Fallback to default thumbnail if image is missing
                    (e.target as HTMLElement).style.display = 'none';
                  }}
                />
              </Link>

              {/* Tag & Reading Time */}
              <div className="flex items-center justify-between gap-2 mb-3">
                <span className="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">
                  {story.tag}
                </span>
                <span className="text-xs font-medium text-slate-400">
                  ⏱ {story.readTime}
                </span>
              </div>

              {/* Story Title */}
              <h2 className="font-heading text-lg font-bold tracking-tight text-slate-900 group-hover:text-amber-800 transition-colors duration-200 line-clamp-2 leading-snug">
                <Link href={`/startup-stories/${story.slug}/`}>
                  {story.title}
                </Link>
              </h2>

              {/* Story Excerpt */}
              <p className="mt-2.5 text-xs sm:text-sm text-slate-600 line-clamp-3 leading-relaxed">
                {story.desc}
              </p>
            </div>

            {/* Card Footer Action */}
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

print(f"Updated {filter_path} with card image thumbnails and no-store live sync!")
