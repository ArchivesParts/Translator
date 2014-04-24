import java.io.BufferedReader;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Iterator;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import javax.swing.event.TableModelListener;
import javax.swing.table.AbstractTableModel;

class Datas extends AbstractTableModel {

    @Override
    public int getRowCount() {
        return translations.size();
    }

    @Override
    public int getColumnCount() {
        return getMaxTranslation();
    }

    @Override
    public String getColumnName(int columnIndex) {
        if (columnIndex == 0) {
            return "source";
        } else {
            return "translation " + columnIndex;
        }
    }

    @Override
    public Class<?> getColumnClass(int columnIndex) {
        return String.class;
    }

    @Override
    public boolean isCellEditable(int rowIndex, int columnIndex) {
        return columnIndex != 0;
    }

    @Override
    public String getValueAt(int rowIndex, int columnIndex) {
        try {
            if (columnIndex == 0) {
                return translations.get(rowIndex).getSource();
            } else {
                List<String> trads = translations.get(rowIndex).getTranslations();
                if (trads.size() >= 0) {
                    return trads.get(columnIndex - 1);
                }
                return "";
            }
        } catch (Exception e) {
            return "";
        }
    }

    @Override
    public void setValueAt(Object aValue, int rowIndex, int columnIndex) {
        translations.get(rowIndex).setTranslation(columnIndex, (String) aValue);
    }

    @Override
    public void addTableModelListener(TableModelListener l) {
    }

    @Override
    public void removeTableModelListener(TableModelListener l) {
    }
    private int maxTranslation = 1;
    private String local;
    private File file;
    private List<Translation> translations;

    public Datas(String local, File file) {
        this.local = local;
        this.file = file;
        this.translations = new ArrayList<Translation>();
        readTranslationFile();
    }

    private void readTranslationFile() {
        BufferedReader reader = null;
        try {
            reader = new BufferedReader(new FileReader(file));
            String line;
            while ((line = reader.readLine()) != null) {
                Pattern pattern = Pattern.compile("['|\"]([^'|\"]+((?<=\\\\)['|\"])*[^'|\"]*)['|\"]");
                Matcher matcher = pattern.matcher(line);
                if (matcher.find()) {
                    Translation translation = new Translation(matcher.group(1));
                    while (matcher.find()) {
                        translation.addTranslation(matcher.group(1));
                    }
                    translations.add(translation);
                    if (translation.nbTranslation() > maxTranslation) {
                        maxTranslation = translation.nbTranslation();
                    }
                }
            }
        } catch (FileNotFoundException ex) {
            Logger.getLogger(Translator.class.getName()).log(Level.SEVERE, null, ex);
        } catch (IOException ex) {
            Logger.getLogger(Translator.class.getName()).log(Level.SEVERE, null, ex);
        } finally {
            try {
                reader.close();
            } catch (IOException ex) {
                Logger.getLogger(Translator.class.getName()).log(Level.SEVERE, null, ex);
            }
        }
    }

    public int getMaxTranslation() {
        return maxTranslation;
    }

    public void save() {
        Collections.sort(translations);

    }

    public void addTranslations(List<String> sources) {
        for (Iterator<String> it = sources.iterator(); it.hasNext();) {
            String source = it.next();
            translations.add(new Translation(source));
        }
    }

    @Override
    public String toString() {
        StringBuilder result = new StringBuilder(local);
        result.append("[").append(file).append("]\n");

        for (Iterator<Translation> it = translations.iterator();
                it.hasNext();) {
            Translation translation = it.next();
            result.append(translation.toString()).append("\n");
        }
        return result.toString();
    }

    public void removeMissingTranslations(List<String> sources) {
        for (Iterator<Translation> it = translations.iterator(); it.hasNext();) {
            Translation translation = it.next();
            if (!sources.contains(translation.getSource())) {
                it.remove();
            }
        }
    }
}
