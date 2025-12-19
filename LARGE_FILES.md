# Large File Support (65MB, 2+ Hours)

## Configuration for Large Audio Files

The system has been configured to support large audio files up to 100MB and 2+ hours in length.

### Changes Made:

1. **File Size Limits:**
   - `MAX_FILE_SIZE`: Increased to 100MB (from 25MB)
   - `upload_max_filesize`: 100M
   - `post_max_size`: 100M

2. **Execution Time:**
   - `max_execution_time`: 3600 seconds (1 hour)
   - `max_input_time`: 3600 seconds
   - Transcription polling: Up to 60 minutes wait time

3. **Memory:**
   - `memory_limit`: 512M

### Important Notes:

#### For PHP Built-in Server:
The `.htaccess` file settings may not apply when using `php -S localhost:8000`. 

**Solution:** Check your system's `php.ini` file or create a custom one:

```bash
# Find your php.ini location
php --ini

# Or use php.ini in project directory (if supported)
php -S localhost:8000 -c php.ini
```

#### For Apache/Nginx:
The `.htaccess` settings should work automatically. If not, update your server's `php.ini` directly.

### Processing Time Expectations:

- **Upload time:** 1-5 minutes (depending on connection)
- **Transcription time:** 
  - 1 hour audio: ~5-10 minutes
  - 2 hour audio: ~10-30 minutes
- **Summary generation:** ~30 seconds - 2 minutes

**Total:** For a 2-hour, 65MB file, expect 15-40 minutes total processing time.

### Browser Considerations:

- Modern browsers can handle long-running requests
- The fetch API has no default timeout
- If the browser times out, you may need to increase server-side timeout settings

### AssemblyAI Limits:

- AssemblyAI supports files of any size
- No hard file size limit
- Processing time scales with audio length
- Free tier: 5 hours of transcription credit

### Troubleshooting:

**Issue:** "File too large" error
- Check `php.ini` settings match `.htaccess`
- Verify `upload_max_filesize` and `post_max_size` are both 100M+
- Restart web server after changes

**Issue:** "Request timeout" error
- Increase `max_execution_time` in php.ini
- Check server timeout settings (Apache/Nginx)
- For PHP built-in server, use `-c php.ini` flag

**Issue:** Transcription takes too long
- This is normal for 2-hour files (10-30 minutes)
- Check AssemblyAI dashboard for processing status
- The system will wait up to 60 minutes for completion

### Testing Large Files:

1. Start with a smaller test file first
2. Verify upload works
3. Then try your 65MB file
4. Be patient - processing takes time!

### Production Recommendations:

- Use a proper web server (Apache/Nginx) instead of PHP built-in server
- Consider using a queue system for very large files
- Monitor server resources during processing
- Set up proper logging for long-running processes


