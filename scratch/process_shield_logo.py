import cv2
import numpy as np
from PIL import Image

src_path = r"C:\Users\Dell\.gemini\antigravity-ide\brain\c90f3c59-5387-4c5a-b545-65e1c15455f1\hamarijobs_stambh_shield_crest_1788337199849.jpg"
dest_frontend = r"c:\hk\hamarijobs\frontend\public\assets\images\logo.png"
dest_backend = r"c:\hk\hamarijobs\backend\public\assets\images\logo.png"

# Load image
img = Image.open(src_path).convert("RGB")
img_np = np.array(img)
rgb = img_np[:, :, :3]
h, w = rgb.shape[:2]

# Find white background
# Pixels that are almost pure white (R>245, G>245, B>245)
white_pixels = (rgb[:, :, 0] > 240) & (rgb[:, :, 1] > 240) & (rgb[:, :, 2] > 240)

# Floodfill from borders to only clear outer white background
mask_corners = np.zeros((h + 2, w + 2), np.uint8)
flood_mask = white_pixels.astype(np.uint8) * 255

# Flood fill from outside edges
cv2.floodFill(flood_mask, mask_corners, (0, 0), 0)
cv2.floodFill(flood_mask, mask_corners, (w-1, 0), 0)
cv2.floodFill(flood_mask, mask_corners, (0, h-1), 0)
cv2.floodFill(flood_mask, mask_corners, (w-1, h-1), 0)
cv2.floodFill(flood_mask, mask_corners, (w//2, 2), 0)
cv2.floodFill(flood_mask, mask_corners, (w//2, h-3), 0)
cv2.floodFill(flood_mask, mask_corners, (2, h//2), 0)
cv2.floodFill(flood_mask, mask_corners, (w-3, h//2), 0)

# Outside background is where flood_mask became 0
outer_bg = (flood_mask == 0)

# Alpha channel
alpha = np.ones((h, w), dtype=np.uint8) * 255
alpha[outer_bg] = 0
alpha = cv2.GaussianBlur(alpha, (3, 3), 0)

result = np.dstack((rgb, alpha))
result_img = Image.fromarray(result, 'RGBA')

# Crop to tight bounds
bbox = result_img.getbbox()
if bbox:
    result_img = result_img.crop(bbox)

# Resize with high quality Lanczos
final_logo = result_img.resize((512, 512), Image.Resampling.LANCZOS)
final_logo.save(dest_frontend, "PNG")
final_logo.save(dest_backend, "PNG")

print("Saved new Ashoka Stambh Shield Crest logo.png successfully!")
