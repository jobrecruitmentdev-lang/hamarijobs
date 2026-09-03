import cv2
import numpy as np
from PIL import Image

src_path = r"C:\Users\Dell\.gemini\antigravity-ide\brain\c90f3c59-5387-4c5a-b545-65e1c15455f1\hamarijobs_prestige_emblem_1788336598472.jpg"
dest_frontend = r"c:\hk\hamarijobs\frontend\public\assets\images\logo.png"
dest_backend = r"c:\hk\hamarijobs\backend\public\assets\images\logo.png"

# Load image
img = Image.open(src_path).convert("RGBA")
datas = img.getdata()

# Process background transparency for outer pure white areas
# Using floodfill / luminance threshold to remove white background outside the emblem
img_np = np.array(img)
rgb = img_np[:, :, :3]

# Create mask of white background (near 255, 255, 255)
# Outside circular bounding box
h, w = rgb.shape[:2]
gray = cv2.cvtColor(rgb, cv2.COLOR_RGB2GRAY)

# Find outer boundary of emblem
thresh = cv2.threshold(gray, 248, 255, cv2.THRESH_BINARY_INV)[1]

# Create transparent image
alpha = np.ones((h, w), dtype=np.uint8) * 255
# Make pure white background pixels (values > 250 in all 3 channels) transparent
white_pixels = (rgb[:, :, 0] > 248) & (rgb[:, :, 1] > 248) & (rgb[:, :, 2] > 248)

# Floodfill from corners to only remove outer background (keep inner white areas intact)
mask_corners = np.zeros((h + 2, w + 2), np.uint8)
flood_mask = white_pixels.astype(np.uint8) * 255

# Seed floodfill at 4 corners
cv2.floodFill(flood_mask, mask_corners, (0, 0), 0)
cv2.floodFill(flood_mask, mask_corners, (w-1, 0), 0)
cv2.floodFill(flood_mask, mask_corners, (0, h-1), 0)
cv2.floodFill(flood_mask, mask_corners, (w-1, h-1), 0)
cv2.floodFill(flood_mask, mask_corners, (w//2, 2), 0)
cv2.floodFill(flood_mask, mask_corners, (w//2, h-3), 0)

# Outside background is where flood_mask became 0
outer_bg = (flood_mask == 0)

# Apply smooth alpha
alpha[outer_bg] = 0
# Smooth edge anti-aliasing
alpha = cv2.GaussianBlur(alpha, (3, 3), 0)

result = np.dstack((rgb, alpha))
result_img = Image.fromarray(result, 'RGBA')

# Crop to tight bounds
bbox = result_img.getbbox()
if bbox:
    result_img = result_img.crop(bbox)

# Resize to standard 512x512 with high quality Lanczos
result_img = result_img.resize((512, 512), Image.Resampling.LANCZOS)

# Save
result_img.save(dest_frontend, "PNG")
result_img.save(dest_backend, "PNG")
print("Processed transparent emblem logo saved successfully to both frontend and backend!")
