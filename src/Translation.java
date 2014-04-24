import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;

class Translation implements Comparable<Translation> {

    private String source;
    private List<String> translations;

    public Translation(String source) {
        this.source = source;
        this.translations = new ArrayList<String>();
    }

    public String getSource() {
        return source;
    }

    public void addTranslation(String translation) {
        translations.add(translation);
    }

    public List<String> getTranslations() {
        return translations;
    }

    @Override
    public String toString() {
        StringBuilder builder = new StringBuilder();
        builder.append(source);
        for (Iterator<String> it = translations.iterator(); it.hasNext();) {
            String translation = it.next();
            builder.append(";").append(translation);

        }
        return builder.toString();
    }

    @Override
    public int compareTo(Translation o) {
        return this.source.compareTo(o.getSource());
    }

    int nbTranslation() {
        return translations.size();
    }

    void setTranslation(int columnIndex, String aValue) {
        while(translations.size()<columnIndex){
            translations.add("");
        }
        translations.set(columnIndex - 1, aValue);
    }
}
